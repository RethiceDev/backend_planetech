<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\SousCategorie;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeEtechnologiesTelephonieCommand extends Command
{
    protected $signature = 'scrape:etechnologies-telephonie
                            {url=https://etechnologies.shop/product-category/telephonie/ : URL de catégorie à scraper}
                            {--force : Mettre à jour même si la photo existe déjà}';

    protected $description = 'Scrape la catégorie Téléphonie E-Technologies SHOP et range dans Réseaux & Télécoms';

    public function handle(): int
    {
        $rawUrl = trim((string) $this->argument('url'));
        $baseUrl = rtrim(preg_replace('#^http://#i', 'https://', $rawUrl), '/');
        $force = (bool) $this->option('force');

        $this->info("Début du scraping sur {$baseUrl}/ ...");

        $reseaux = Category::firstOrCreate(
            ['nom' => 'Réseaux & Télécoms'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $sousCategoriesList = [
            'Câbles Réseau et Télécoms',
            'Fibre Optique',
            'Connectique, Prises et Brassage',
            'Équipements Actifs (Switchs, Routeurs, Wi-Fi)',
            'Baies et Coffrets de Brassage',
            'Téléphonie et Communication',
            'Antennes, TV et Satellite',
            'Vidéosurveillance IP',
            'Outillage et Mesure Réseau',
        ];

        $sousCategories = [];
        foreach ($sousCategoriesList as $nomSub) {
            $sousCategories[$nomSub] = SousCategorie::firstOrCreate(
                [
                    'category_id' => $reseaux->id,
                    'nom' => $nomSub,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $totalInserted = 0;
        $totalUpdated = 0;

        for ($page = 1; $page <= 20; $page++) {
            $url = $page === 1 ? $baseUrl . '/' : $baseUrl . '/page/' . $page . '/';
            $this->info("\n--- Page {$page} : {$url} ---");

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                ])
                    ->withOptions(['verify' => false])
                    ->connectTimeout(25)
                    ->timeout(60)
                    ->retry(2, 2500)
                    ->get($url);
            } catch (ConnectionException $e) {
                $this->warn('Erreur réseau : ' . $e->getMessage());
                break;
            }

            $status = $response->status();
            $body = $response->body();

            if ($status === 404) {
                $this->line('Fin de pagination (404).');
                break;
            }

            if ($status === 403 || str_contains($body, 'Just a moment...')) {
                $this->warn("Blocage anti-bot (403) sur {$url}.");
                break;
            }

            if ($response->failed()) {
                $this->warn("Erreur HTTP {$status} sur {$url}");
                break;
            }

            $crawler = new Crawler($body);
            $nodes = $this->extractProductNodes($crawler);
            $this->info('Produits détectés : ' . $nodes->count() . ' (HTTP ' . $status . ')');

            if ($nodes->count() === 0) {
                break;
            }

            foreach ($nodes as $domElement) {
                try {
                    if (!$domElement instanceof \DOMElement) {
                        continue;
                    }

                    $node = new Crawler($domElement);
                    $name = $this->extractProductName($node);

                    if ($this->isJunkName($name)) {
                        continue;
                    }

                    $productUrl = $this->extractProductUrl($node);
                    $subCat = $this->resolveSousCategorie($name, $productUrl, $baseUrl, $sousCategories);

                    $product = Product::where('sous_categorie_id', $subCat->id)
                        ->where('name', $name)
                        ->first();

                    if ($product && !empty($product->photo) && !$force) {
                        continue;
                    }

                    $imageUrl = $this->extractImageUrl($node, $productUrl, $baseUrl . '/');
                    $localImage = $this->downloadImage($imageUrl, $name, $baseUrl . '/');
                    $price = $this->extractPrice($node);

                    if ($product) {
                        $product->update([
                            'price' => $price ?? $product->price,
                            'photo' => $localImage ?? $product->photo,
                        ]);
                        $totalUpdated++;
                        $this->line('  [🔄] Mis à jour : ' . Str::limit($name, 45) . ' -> [' . $subCat->nom . ']');
                    } else {
                        Product::create([
                            'sous_categorie_id' => $subCat->id,
                            'name' => $name,
                            'price' => $price,
                            'photo' => $localImage,
                            'description' => 'Produit Téléphonie & Communication — E-Technologies SHOP.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $totalInserted++;
                        $this->line('  [✔] Inséré : ' . Str::limit($name, 45) . ' -> [' . $subCat->nom . ']');
                    }
                } catch (\Throwable $e) {
                    $this->error('  [X] Erreur produit : ' . $e->getMessage());
                }
            }

            $nextPage = $crawler->filter('a.next.page-numbers, .woocommerce-pagination a.next, [rel="next"]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Insérés : {$totalInserted} | Mis à jour : {$totalUpdated}");
        return self::SUCCESS;
    }

    private function resolveSousCategorie(string $name, string $productUrl, string $baseUrl, array $sousCategories): SousCategorie
    {
        $text = mb_strtolower($name . ' ' . $productUrl . ' ' . $baseUrl);

        $rules = [
            'Téléphonie et Communication' => [
                'strong' => ['téléphone', 'telephone', 'combiné', 'combine', 'interphone', 'visiophone', 'autocommutateur', 'pabx', 'ipbx', 'casque téléphonique', 'casque telephonique', 'dect'],
                'weak' => ['voip', 'sip', 'gsm', 'rj11', 'rj12', 'appel', 'communication'],
            ],
            'Vidéosurveillance IP' => [
                'strong' => ['caméra ip', 'camera ip', 'vidéosurveillance', 'videosurveillance', 'nvr', 'dvr'],
                'weak' => ['caméra', 'camera', 'cctv'],
            ],
            'Équipements Actifs (Switchs, Routeurs, Wi-Fi)' => [
                'strong' => ['switch', 'routeur', 'router', 'passerelle voip', 'gateway', 'wi-fi', 'wifi', 'access point'],
                'weak' => ['poe', 'port'],
            ],
            'Connectique, Prises et Brassage' => [
                'strong' => ['prise téléphonique', 'prise telephonique', 'cordon téléphonique', 'cordon telephonique', 'filtre adsl', 'connecteur rj11', 'connecteur rj12', 'jarretière', 'jarretiere'],
                'weak' => ['prise', 'connecteur', 'adaptateur', 'coupleur', 'cordon', 'rj11', 'rj12', 'rj45'],
            ],
            'Câbles Réseau et Télécoms' => [
                'strong' => ['câble téléphonique', 'cable telephonique', 'câble télécom', 'cable telecom', 'touret téléphonique'],
                'weak' => ['câble', 'cable', 'touret', 'paire'],
            ],
            'Baies et Coffrets de Brassage' => [
                'strong' => ['baie de brassage', 'coffret de brassage', 'armoire réseau', 'armoire reseau', 'rack 19'],
                'weak' => ['baie', 'coffret', 'rack', 'armoire', 'répartiteur'],
            ],
            'Fibre Optique' => [
                'strong' => ['fibre optique', 'jarretière optique', 'jarretiere optique', 'ftth'],
                'weak' => ['fibre', 'optique', 'sfp'],
            ],
            'Antennes, TV et Satellite' => [
                'strong' => ['antenne gsm', 'amplificateur gsm', 'récepteur', 'coaxial'],
                'weak' => ['antenne', 'coax'],
            ],
            'Outillage et Mesure Réseau' => [
                'strong' => ['testeur', 'sertisseuse', 'dénudeuse', 'denudeuse'],
                'weak' => ['mesure', 'outillage', 'pince'],
            ],
        ];

        $bestCategory = 'Téléphonie et Communication';
        $bestScore = 0;

        foreach ($rules as $catName => $groups) {
            $score = 0;
            foreach ($groups['strong'] as $kw) {
                if (str_contains($text, $kw)) {
                    $score += 3;
                }
            }
            foreach ($groups['weak'] as $kw) {
                if (str_contains($text, $kw)) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $catName;
            }
        }

        return $sousCategories[$bestCategory];
    }

    private function extractProductNodes(Crawler $crawler): Crawler
    {
        $selectors = [
            'ul.products li.product',
            '.products .product',
            'li.type-product',
            'div.product.type-product',
            'article.product',
            '[data-product-id]',
        ];

        foreach ($selectors as $selector) {
            $found = $crawler->filter($selector);
            if ($found->count() > 0) {
                return $found;
            }
        }

        return $crawler->filter('a[href*="/product/"]');
    }

    private function extractProductName(Crawler $node): string
    {
        $titleSelectors = [
            '.woocommerce-loop-product__title',
            'h2.woocommerce-loop-product__title',
            'h2',
            'h3',
        ];

        foreach ($titleSelectors as $selector) {
            $titleNode = $node->filter($selector);
            if ($titleNode->count() > 0) {
                $candidate = trim($titleNode->first()->text());
                if (!$this->isJunkName($candidate)) {
                    return $candidate;
                }
            }
        }

        foreach ($node->filter('a') as $el) {
            if ($el instanceof \DOMElement) {
                $parentClass = $el->parentNode instanceof \DOMElement ? (string) $el->parentNode->getAttribute('class') : '';
                $elClass = (string) $el->getAttribute('class');
                if (str_contains($elClass, 'compare') || str_contains($parentClass, 'compare')) {
                    continue;
                }

                $txt = trim((string) $el->textContent);
                if (!$this->isJunkName($txt)) {
                    return $txt;
                }
            }
        }

        return '';
    }

    private function isJunkName(string $name): bool
    {
        $clean = trim($name);
        if ($clean === '' || mb_strlen($clean) <= 5) {
            return true;
        }

        $lower = mb_strtolower($clean);
        $forbidden = [
            'comparer',
            'ajouter',
            'lire la suite',
            'voir le produit',
            'sélectionner des options',
            'apercu rapide',
            'aperçu rapide',
        ];

        foreach ($forbidden as $word) {
            if ($lower === $word || str_contains($lower, $word)) {
                return true;
            }
        }

        return false;
    }

    private function extractProductUrl(Crawler $node): string
    {
        $linkNode = $node->filter('a.woocommerce-LoopProduct-link, h2.woocommerce-loop-product__title a, .woocommerce-loop-product__title a, h2 a, h3 a, a[href*="/product/"]');
        if ($linkNode->count() > 0) {
            $href = trim((string) $linkNode->first()->attr('href'));
            if ($href !== '' && !Str::startsWith($href, ['http://', 'https://'])) {
                return 'https://etechnologies.shop/' . ltrim($href, '/');
            }
            return $href;
        }

        return '';
    }

    private function extractImageUrl(Crawler $node, ?string $productUrl, string $referer): ?string
    {
        $imgNode = $node->filter('img.attachment-woocommerce_thumbnail, img.wp-post-image, img');
        if ($imgNode->count() > 0) {
            $src = trim((string) ($imgNode->first()->attr('data-src') ?: $imgNode->first()->attr('src')));
            if ($src !== '' && !Str::contains($src, ['data:image', 'placeholder'])) {
                return str_starts_with($src, '//') ? 'https:' . $src : $src;
            }
        }

        if (empty($productUrl)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Referer' => $referer,
            ])
                ->withOptions(['verify' => false])
                ->connectTimeout(15)
                ->timeout(25)
                ->get($productUrl);

            if ($response->successful()) {
                $crawler = new Crawler($response->body());
                $ogImage = $crawler->filter('meta[property="og:image"]');
                if ($ogImage->count() > 0) {
                    $url = trim((string) $ogImage->first()->attr('content'));
                    return str_starts_with($url, '//') ? 'https:' . $url : $url;
                }
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private function downloadImage(?string $imageUrl, string $name, string $referer): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Referer' => $referer,
            ])
                ->withOptions(['verify' => false])
                ->connectTimeout(15)
                ->timeout(30)
                ->get($imageUrl);

            if ($response->successful() && !empty($response->body())) {
                $imageName = Str::slug(Str::limit($name, 40, '')) . '-' . time() . '-' . random_int(100, 999) . '.jpg';
                Storage::disk('public')->put('products/' . $imageName, $response->body());
                return 'storage/products/' . $imageName;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private function extractPrice(Crawler $node): ?float
    {
        $priceNode = $node->filter('.price .woocommerce-Price-amount, .price, span[itemprop="price"]');
        $priceText = $priceNode->count() > 0 ? $priceNode->first()->text() : $node->text();

        if (preg_match('/([\d\s\.,]+)\s*(?:XOF|FCFA|€)/i', $priceText, $matches)) {
            $clean = preg_replace('/[^\d]/', '', $matches[1]);
            if ($clean !== '' && is_numeric($clean)) {
                return (float) $clean;
            }
        }

        return null;
    }
}