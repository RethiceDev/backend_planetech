<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\SousCategorie;
use App\Services\ProductClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeSmartCommand extends Command
{
    protected $signature = 'scrape:smart {url}
                            {--depth=4 : Profondeur max des sous-catégories}
                            {--limit=0 : Nombre max de produits (0 = illimité)}
                            {--delay=150 : Pause entre requêtes (ms)}
                            {--dry-run : Affiche le classement sans rien enregistrer}';

    protected $description = 'Scrape en profondeur (catégories, sous-catégories, pagination, produits parents), classe chaque produit et télécharge les images';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    private ProductClassifier $classifier;
    private $sousCats;       // keyBy nom
    private $sousCatsById;   // keyBy id
    private $categories;     // keyBy id
    private SousCategorie $diversSousCat;

    private string $host = '';
    private string $scopePath = '/';

    private array $visitedListings = [];
    private array $visitedProducts = [];
    private array $stats = [];
    private array $thumbs = [];
    private int $total = 0;
    private int $imagesKo = 0;
    private bool $dry = false;

    public function handle()
    {
        $startUrl = $this->cleanUrl($this->argument('url'));
        $this->host = (string) parse_url($startUrl, PHP_URL_HOST);
        $this->scopePath = rtrim(parse_url($startUrl, PHP_URL_PATH) ?: '/', '/') . '/';
        $this->dry = (bool) $this->option('dry-run');

        $this->classifier   = new ProductClassifier();
        $this->sousCats     = SousCategorie::all()->keyBy('nom');
        $this->sousCatsById = SousCategorie::all()->keyBy('id');
        $this->categories   = Category::all()->keyBy('id');

        // Catégorie "Divers" : on réutilise sa 1re sous-catégorie, sinon on la crée
        $divers = Category::firstOrCreate(['nom' => 'Divers']);
        $this->diversSousCat = SousCategorie::where('category_id', $divers->id)->first()
            ?? SousCategorie::firstOrCreate(['nom' => 'Divers', 'category_id' => $divers->id]);
        $this->categories->put($divers->id, $divers);

        $this->info('=== SCRAPING INTELLIGENT' . ($this->dry ? ' (DRY-RUN : rien ne sera enregistré)' : '') . ' ===');
        $this->info("Départ : {$startUrl}");

        $this->crawlListing($startUrl, 0, '');

        $this->info("\n=== TERMINÉ ===");
        $this->info("Produits traités : {$this->total} | images en échec : {$this->imagesKo}");
        if ($this->stats) {
            arsort($this->stats);
            $this->table(['Sous-catégorie', 'Produits'], collect($this->stats)->map(fn ($n, $k) => [$k, $n])->values()->all());
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Listes de produits / sous-catégories / pagination                  */
    /* ------------------------------------------------------------------ */

    private function crawlListing(string $url, int $depth, string $parentCtx): void
    {
        if ($depth > (int) $this->option('depth') || $this->limitReached()) return;

        $key = $this->cleanUrl($url);
        if (isset($this->visitedListings[$key])) return;

        $subLinks = [];
        $listingCtx = $parentCtx;
        $page = $key;
        $guard = 0;

        while ($page && $guard < 60 && !$this->limitReached()) {
            if (isset($this->visitedListings[$page]) && $page !== $key) break;
            $this->visitedListings[$page] = true;
            $guard++;

            $html = $this->fetch($page);
            if (!$html) break;

            $crawler = new Crawler($html);

            if ($guard === 1) {
                $listingCtx = trim($parentCtx . ' ' . $this->listingContext($crawler, $page));
                $this->info("\n--- Section (profondeur {$depth}) : " . Str::limit($listingCtx, 90) . " ---");
            }

            $productLinks = $this->extractProductLinks($crawler, $page);
            $this->line('   ' . count($productLinks) . ' produit(s) sur cette page');

            foreach ($productLinks as $pUrl) {
                if ($this->limitReached()) return;
                $this->processProduct($pUrl, $listingCtx);
            }

            foreach ($this->extractSubCategoryLinks($crawler, $page) as $s) {
                $subLinks[$s] = true;
            }

            $page = $productLinks ? $this->nextPageUrl($crawler, $page) : null;
        }

        // Descente dans les sous-catégories (produits cachés en profondeur)
        foreach (array_keys($subLinks) as $sub) {
            if ($this->limitReached()) return;
            $this->crawlListing($sub, $depth + 1, $listingCtx);
        }
    }

    private function extractProductLinks(Crawler $crawler, string $pageUrl): array
    {
        $links = [];

        $cards = $crawler->filter('li.product:not(.product-category), .product-small:not(.product-category), .products .product:not(.product-category), .product-item, .type-product');
        $cards->each(function (Crawler $card) use (&$links, $pageUrl) {
            $a = $card->filter('a.woocommerce-LoopProduct-link, .woocommerce-loop-product__title a, .name a, h2 a, h3 a, a.product-image, .box-image a, a[href]')->first();
            if ($a->count() === 0) return;
            $u = $this->absolutize((string) $a->attr('href'), $pageUrl);
            if ($u && $this->isProductUrl($u)) $links[$this->cleanUrl($u)] = true;
        });

        // Filet de sécurité si le thème n'utilise pas les classes WooCommerce
        if (!$links) {
            $crawler->filter('a[href]')->each(function (Crawler $a) use (&$links, $pageUrl) {
                $u = $this->absolutize((string) $a->attr('href'), $pageUrl);
                if ($u && preg_match('~/(produit|product|produits)/[^/]+~i', $u) && $this->isProductUrl($u)) {
                    $k = $this->cleanUrl($u);
                    $links[$k] = true;
                    if (empty($this->thumbs[$k])) {
                        $t = $this->thumbFromAnchor($a, $pageUrl);
                        if ($t) $this->thumbs[$k] = $t;
                    }
                }
            });
        }

        return array_keys($links);
    }

    private function extractSubCategoryLinks(Crawler $crawler, string $pageUrl): array
    {
        $links = [];
        $currentPath = rtrim((string) parse_url($pageUrl, PHP_URL_PATH), '/') . '/';

        $crawler->filter('a[href]')
            ->each(function (Crawler $a) use (&$links, $pageUrl, $currentPath) {
                $u = $this->absolutize((string) $a->attr('href'), $pageUrl);
                if (!$u || parse_url($u, PHP_URL_HOST) !== $this->host) return;
                $u = $this->cleanUrl($u);
                $path = rtrim((string) parse_url($u, PHP_URL_PATH), '/') . '/';
                if ($path === $currentPath) return;
                if (!str_starts_with($path, $currentPath)) return; // uniquement les descendants de la page courante
                if (!preg_match('~categor|product-cat|rayon|collection~i', $path)) return;

                // On reste dans le périmètre de l'URL de départ (évite de scraper tout le site par erreur)
                if ($this->scopePath !== '/' && !str_starts_with($path, $this->scopePath)) return;

                $links[$u] = true;
            });

        return array_keys($links);
    }

    private function nextPageUrl(Crawler $crawler, string $pageUrl): ?string
    {
        foreach (['a.next.page-numbers', 'a[rel=next]', 'link[rel=next]', '.next-page a'] as $sel) {
            $n = $crawler->filter($sel)->first();
            if ($n->count() && $n->attr('href')) {
                $u = $this->absolutize((string) $n->attr('href'), $pageUrl);
                if ($u) return $this->cleanUrl($u);
            }
        }
        return null;
    }

    private function listingContext(Crawler $crawler, string $pageUrl): string
    {
        $parts = [];
        foreach (['.woocommerce-breadcrumb', 'h1'] as $sel) {
            $n = $crawler->filter($sel)->first();
            if ($n->count()) $parts[] = $this->clean($n->text());
        }
        $slug = str_replace(['-', '/', '_'], ' ', (string) parse_url($pageUrl, PHP_URL_PATH));
        $parts[] = $slug;

        return implode(' ', array_filter($parts));
    }

    private function isProductUrl(string $u): bool
    {
        if (parse_url($u, PHP_URL_HOST) !== $this->host) return false;
        if (preg_match('~add-to-cart|/panier|/cart|/checkout|/wishlist|/compare|/mon-compte|/my-account|/tag/|/categorie-produit/|/product-category/|\.(jpg|png|webp|pdf)$~i', $u)) return false;
        return true;
    }

    /* ------------------------------------------------------------------ */
    /*  Fiche produit                                                      */
    /* ------------------------------------------------------------------ */

    private function processProduct(string $url, string $listingCtx): void
    {
        $key = $this->cleanUrl($url);
        if (isset($this->visitedProducts[$key])) return;
        $this->visitedProducts[$key] = true;

        $html = $this->fetch($key);
        if (!$html) return;

        $pc = new Crawler($html);

        // Produit PARENT (groupé) : on descend dans les produits enfants
        $children = $this->childProductLinks($pc, $key);
        if ($children) {
            $parentTitle = $this->extractTitle($pc, $this->extractJsonLd($pc));
            $this->line("   ⤷ <fg=yellow>Produit parent</> « {$parentTitle} » : " . count($children) . ' sous-produit(s)');
            foreach ($children as $child) {
                if ($this->limitReached()) return;
                $this->processProduct($child, trim($listingCtx . ' ' . $parentTitle));
            }
            return;
        }

        $ld    = $this->extractJsonLd($pc);
        $title = $this->extractTitle($pc, $ld);
        if ($title === '') return;

        $description = $this->extractDescription($pc, $ld);

        // Contexte : fil d'Ariane + catégories du produit + catégorie de la liste d'où on vient
        $ctxParts = [$listingCtx];
        foreach (['.woocommerce-breadcrumb', '.posted_in'] as $sel) {
            $n = $pc->filter($sel)->first();
            if ($n->count()) $ctxParts[] = $this->clean($n->text());
        }
        $context = implode(' ', array_filter($ctxParts));

        // ---- Classement ----
        $res = $this->classifier->classify($title, $description, $context);
        $sousCat = $res['name'] ? $this->sousCats->get($res['name']) : null;

        if ($res['name'] && !$sousCat) {
            $this->warn("   ! Sous-catégorie « {$res['name']} » introuvable en base (seeder non lancé ?) -> Divers");
        }
        $isDivers = !$sousCat;
        if ($isDivers) $sousCat = $this->diversSousCat;

        $catName = $this->categories->get($sousCat->category_id)->nom ?? '?';
        $label   = "{$catName} › {$sousCat->nom}";
        $why     = $isDivers
            ? 'aucune règle au-dessus du seuil (meilleur score ' . $res['score'] . ')'
            : 'score ' . $res['score'] . ' — ' . implode(', ', $res['hits']);

        $this->line("   ➔ Produit : <fg=cyan>{$title}</>");
        $this->line("      Rangé dans : <fg=" . ($isDivers ? 'yellow' : 'green') . ";options=bold>[{$label}]</> <fg=gray>({$why})</>");
        if (!$isDivers && $res['ambiguous'] && $res['alts']) {
            $this->line("      <fg=yellow>⚠ ambigu</> alternative : {$res['alts'][0]['name']} (score {$res['alts'][0]['score']})");
        }

        // ---- Image ----
        $existing = Product::where('name', $title)->first();
        $photo = null;
        if ($existing && $existing->photo && Storage::disk('public')->exists($existing->photo)) {
            $photo = $existing->photo;
            $this->line("      Image : déjà présente ({$photo})");
        } elseif (!$this->dry) {
            [$photo, $err] = $this->downloadFirstValidImage($this->imageCandidates($pc, $key, $ld, $this->thumbs[$key] ?? null), $title, $key);
            if ($photo) {
                $this->line("      Image : <fg=green>OK</> {$photo}");
            } else {
                $this->imagesKo++;
                $this->line("      Image : <fg=red>échec</> ({$err})");
            }
        }

        if (!$this->dry) {
            $values = [
                'sous_categorie_id' => $sousCat->id,
                'description'       => $description,
                'price'             => null,
                'stock'             => true,
            ];
            if ($photo) $values['photo'] = $photo;

            Product::updateOrCreate(['name' => $title], $values);
        }

        $this->stats[$label] = ($this->stats[$label] ?? 0) + 1;
        $this->total++;
    }

    private function thumbFromAnchor(Crawler $a, string $pageUrl): ?string
    {
        $node = $a->getNode(0);
        while ($node && strtolower($node->nodeName) !== 'li') $node = $node->parentNode;
        if (!$node) return null;

        $img = (new Crawler($node))->filter('img')->first();
        if (!$img->count()) return null;

        foreach (['data-src', 'data-lazy-src', 'src'] as $attr) {
            $v = $img->attr($attr);
            if ($v && !str_starts_with($v, 'data:')) return $this->absolutize($v, $pageUrl);
        }
        return null;
    }

    /** Pour les thèmes non-WooCommerce : texte du bloc qui entoure le <h1>, sans « Produits apparentés ». */
    private function descriptionFromTitleContainer(Crawler $pc): string
    {
        $h1 = $pc->filter('h1')->first();
        if (!$h1->count()) return '';
        $title = $this->clean($h1->text());

        $node = $h1->getNode(0)->parentNode;
        for ($i = 0; $i < 6 && $node; $i++, $node = $node->parentNode) {
            if (in_array(strtolower($node->nodeName), ['body', 'html', '#document', 'header', 'footer', 'nav'], true)) break;

            $text = $this->clean((new Crawler($node))->text());
            if (mb_strlen($text) <= mb_strlen($title) + 80) continue;

            $text = preg_split('~Produits? apparent[ée]s?|Produits similaires|Related products~iu', $text)[0];
            $pos = mb_strrpos($text, $title);
            if ($pos !== false) $text = mb_substr($text, $pos + mb_strlen($title));

            return trim($text);
        }
        return '';
    }

    private function childProductLinks(Crawler $pc, string $pageUrl): array
    {
        $links = [];
        $sel = 'table.woocommerce-grouped-product-list a[href], .woocommerce-grouped-product-list-item__label a[href], form.grouped_form a[href]';
        $pc->filter($sel)->each(function (Crawler $a) use (&$links, $pageUrl) {
            $u = $this->absolutize((string) $a->attr('href'), $pageUrl);
            if ($u && $this->isProductUrl($u) && $this->cleanUrl($u) !== $this->cleanUrl($pageUrl)) {
                $links[$this->cleanUrl($u)] = true;
            }
        });
        return array_keys($links);
    }

    private function extractTitle(Crawler $pc, array $ld): string
    {
        foreach (['h1.product_title', 'h1.entry-title', 'h1'] as $sel) {
            $n = $pc->filter($sel)->first();
            if ($n->count()) {
                $t = $this->clean($n->text());
                if ($t !== '') return $t;
            }
        }
        return $this->clean((string) ($ld['name'] ?? ''));
    }

    private function extractDescription(Crawler $pc, array $ld): string
    {
        $chunks = [];
        foreach (['.woocommerce-product-details__short-description', '.product-short-description', '#tab-description'] as $sel) {
            $n = $pc->filter($sel)->first();
            if ($n->count()) $chunks[] = $this->clean($n->text());
        }
        $desc = trim(implode(' ', array_filter($chunks)));

        if ($desc === '') $desc = $this->descriptionFromTitleContainer($pc);
        if ($desc === '') $desc = $this->clean((string) ($ld['description'] ?? ''));
        if ($desc === '') {
            $m = $pc->filter('meta[property="og:description"], meta[name="description"]')->first();
            if ($m->count()) $desc = $this->clean((string) $m->attr('content'));
        }
        return Str::limit($desc, 3000, '');
    }

    /* ------------------------------------------------------------------ */
    /*  Images                                                             */
    /* ------------------------------------------------------------------ */

    private function imageCandidates(Crawler $pc, string $pageUrl, array $ld, ?string $thumb = null): array
    {
        $c = [];
        $push = function (?string $u) use (&$c, $pageUrl) {
            $u = trim((string) $u);
            if ($u === '' || str_starts_with($u, 'data:')) return;
            if (str_contains($u, ',') && preg_match('~\s\d+(w|x)~', $u)) $u = $this->bestFromSrcset($u);
            $abs = $this->absolutize($u, $pageUrl);
            if (!$abs) return;
            $file = basename((string) parse_url($abs, PHP_URL_PATH));
            if (preg_match('~placeholder|blank|spinner|loader|lazy~i', $file)) return;
            if (!in_array($abs, $c, true)) $c[] = $abs;
        };

        // 1. Galerie WooCommerce : lien plein format puis attributs lazy-load
        $g = $pc->filter('.woocommerce-product-gallery__image')->first();
        if ($g->count()) {
            $a = $g->filter('a[href]')->first();
            if ($a->count()) $push($a->attr('href'));
            $img = $g->filter('img')->first();
            if ($img->count()) {
                foreach (['data-large_image', 'data-src', 'data-lazy-src', 'data-srcset', 'srcset', 'src'] as $attr) {
                    $push($img->attr($attr));
                }
            }
        }

        // 2. JSON-LD
        $ldImg = $ld['image'] ?? null;
        if (is_array($ldImg)) $ldImg = $ldImg['url'] ?? ($ldImg[0]['url'] ?? ($ldImg[0] ?? null));
        if (is_string($ldImg)) $push($ldImg);

        // 3. og:image
        $og = $pc->filter('meta[property="og:image"]')->first();
        if ($og->count()) $push($og->attr('content'));

        // 3b. Images du bloc qui contient le titre (hors produits apparentés = images dans un <li>)
        $h1 = $pc->filter('h1')->first();
        if ($h1->count()) {
            $node = $h1->getNode(0)->parentNode;
            for ($i = 0; $i < 5 && $node; $i++, $node = $node->parentNode) {
                if (in_array(strtolower($node->nodeName), ['body', 'html', '#document', 'header', 'footer', 'nav'], true)) break;
                $found = false;
                (new Crawler($node))->filter('img')->each(function (Crawler $img) use ($push, &$found) {
                    $n = $img->getNode(0);
                    while ($n = $n->parentNode) {
                        if (strtolower($n->nodeName) === 'li') return;
                    }
                    foreach (['data-large_image', 'data-src', 'data-lazy-src', 'data-srcset', 'srcset', 'src'] as $attr) {
                        $push($img->attr($attr));
                    }
                    $found = true;
                });
                if ($found) break;
            }
        }

        // 4. Autres images produit
        $pc->filter('.product-images img, .attachment-shop_single, .wp-post-image, .woocommerce-main-image img')->each(function (Crawler $img) use ($push) {
            foreach (['data-large_image', 'data-src', 'data-lazy-src', 'data-srcset', 'srcset', 'src'] as $attr) {
                $push($img->attr($attr));
            }
        });

        // 5. Dernier recours : miniature vue dans la liste de la catégorie
        $push($thumb);

        return $c;
    }

    private function bestFromSrcset(string $srcset): string
    {
        $best = ''; $bestW = -1;
        foreach (explode(',', $srcset) as $part) {
            $bits = preg_split('~\s+~', trim($part));
            $w = isset($bits[1]) ? (int) $bits[1] : 0;
            if ($bits[0] !== '' && $w >= $bestW) { $best = $bits[0]; $bestW = $w; }
        }
        return $best;
    }

    private function downloadFirstValidImage(array $candidates, string $title, string $referer): array
    {
        if (!$candidates) return [null, 'aucune URL d\'image trouvée dans la page'];

        $lastErr = 'inconnue';
        foreach (array_slice($candidates, 0, 6) as $url) {
            try {
                $r = Http::withHeaders([
                    'User-Agent' => self::UA,
                    'Referer'    => $referer,
                    'Accept'     => 'image/avif,image/webp,image/*,*/*;q=0.8',
                ])->timeout(25)->get($url);

                if (!$r->successful()) { $lastErr = 'HTTP ' . $r->status() . ' sur ' . basename($url); continue; }

                $body = $r->body();
                $info = @getimagesizefromstring($body);
                if (!$info || strlen($body) < 800) { $lastErr = 'le fichier reçu n\'est pas une image (' . basename($url) . ')'; continue; }

                $ext = match ($info['mime']) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif',
                    'image/avif' => 'avif',
                    default      => null,
                };
                if (!$ext) { $lastErr = 'format non géré : ' . $info['mime']; continue; }

                $path = 'produits/' . Str::limit(Str::slug($title), 80, '') . '-' . substr(md5($url), 0, 8) . '.' . $ext;
                Storage::disk('public')->put($path, $body);
                return [$path, null];
            } catch (\Throwable $e) {
                $lastErr = Str::limit($e->getMessage(), 90);
            }
        }
        return [null, $lastErr];
    }

    /* ------------------------------------------------------------------ */
    /*  Utilitaires                                                        */
    /* ------------------------------------------------------------------ */

    private function extractJsonLd(Crawler $pc): array
    {
        $found = [];
        $pc->filter('script[type="application/ld+json"]')->each(function (Crawler $s) use (&$found) {
            $data = json_decode(trim($s->text()), true);
            if (!is_array($data)) return;
            $nodes = isset($data['@graph']) ? $data['@graph'] : (array_is_list($data) ? $data : [$data]);
            foreach ($nodes as $n) {
                if (is_array($n) && isset($n['@type']) && in_array('Product', (array) $n['@type'], true) && !$found) {
                    $found = $n;
                }
            }
        });
        return $found;
    }

    private function fetch(string $url): ?string
    {
        usleep(max(0, (int) $this->option('delay')) * 1000);
        try {
            $r = Http::withHeaders([
                'User-Agent'      => self::UA,
                'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
            ])->timeout(30)->retry(2, 500, null, false)->get($url);

            if (!$r->successful()) {
                $this->warn("   ! HTTP {$r->status()} : {$url}");
                return null;
            }
            return $r->body();
        } catch (\Throwable $e) {
            $this->warn('   ! ' . Str::limit($e->getMessage(), 100) . " : {$url}");
            return null;
        }
    }

    private function absolutize(string $u, string $base): ?string
    {
        $u = str_replace(' ', '%20', trim($u));
        if ($u === '' || str_starts_with($u, '#') || str_starts_with($u, 'javascript:') || str_starts_with($u, 'mailto:') || str_starts_with($u, 'tel:')) return null;
        if (preg_match('~^https?://~i', $u)) return $u;
        if (str_starts_with($u, '//')) return 'https:' . $u;

        $p = parse_url($base);
        if (!$p || empty($p['host'])) return null;
        $root = ($p['scheme'] ?? 'https') . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');

        if (str_starts_with($u, '/')) return $root . $u;

        $dir = rtrim(dirname($p['path'] ?? '/'), '/');
        return $root . $dir . '/' . $u;
    }

    private function cleanUrl(string $url): string
    {
        $url = trim($url);
        $p = parse_url($url);
        if (!$p || empty($p['host'])) return $url;

        $q = [];
        if (!empty($p['query'])) {
            parse_str($p['query'], $q);
            foreach (['srsltid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'] as $k) unset($q[$k]);
        }

        return ($p['scheme'] ?? 'https') . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '')
            . ($p['path'] ?? '/') . ($q ? '?' . http_build_query($q) : '');
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('~\s+~u', ' ', $text));
    }

    private function limitReached(): bool
    {
        $l = (int) $this->option('limit');
        return $l > 0 && $this->total >= $l;
    }
}
