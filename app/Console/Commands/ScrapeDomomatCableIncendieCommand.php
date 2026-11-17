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

class ScrapeDomomatCableIncendieCommand extends Command
{
    protected $signature = 'scrape:domomat-incendie
                            {url=https://www.domomat.com/1231-cables-securite-incendie : URL de catégorie à scraper}
                            {--fallback= : URL directe de secours si 403}
                            {--force : Mettre à jour même si la photo existe déjà}';

    protected $description = 'Scrape les câbles sécurité incendie (sur devis) en filtrant les câbles réseau vers Électricité Générale > Câbles et Conducteurs';

    public function handle(): int
    {
        $rawUrl = trim((string) $this->argument('url'));
        $primaryUrl = rtrim(preg_replace('#^http://#i', 'https://', $rawUrl), '/');
        $fallbackUrl = rtrim(trim((string) $this->option('fallback')), '/');
        $force = (bool) $this->option('force');

        $this->info("Début du scraping sur {$primaryUrl} ...");

        $electricite = Category::firstOrCreate(
            ['nom' => 'Électricité Générale'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $targetSubCat = SousCategorie::firstOrCreate(
            [
                'category_id' => $electricite->id,
                'nom' => 'Câbles et Conducteurs',
            ],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $activeBaseUrl = $primaryUrl;
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalSkippedNetwork = 0;

        for ($page = 1; $page <= 20; $page++) {
            $url = $page === 1
                ? $activeBaseUrl
                : $activeBaseUrl . (str_contains($activeBaseUrl, '?') ? '&page=' . $page : '?page=' . $page);

            $this->info("\n--- Page {$page} : {$url} ---");

            $fetch = $this->fetchHtml($url);

            if ($page === 1 && ($fetch['status'] === 403 || $fetch['blocked']) && $fallbackUrl !== '') {
                $this->warn("Blocage anti-bot (403) sur {$primaryUrl} -> bascule sur {$fallbackUrl}");
                $activeBaseUrl = $fallbackUrl;
                $url = $activeBaseUrl;
                $fetch = $this->fetchHtml($url);
            }

            if ($fetch['status'] === 404) {
                $this->line('Fin de pagination (404).');
                break;
            }

            if ($fetch['blocked'] || $fetch['status'] === 403) {
                $this->warn("Blocage anti-bot (HTTP {$fetch['status']}) sur {$url}.");
                break;
            }

            if ($fetch['failed'] || $fetch['body'] === '') {
                $this->warn("Impossible de récupérer la page (HTTP {$fetch['status']}).");
                break;
            }

            $crawler = new Crawler($fetch['body']);
            $items = $this->extractItemsFromHtml($crawler, $activeBaseUrl);
            $this->info('Éléments détectés : ' . count($items) . ' (HTTP ' . $fetch['status'] . ')');

            if (count($items) === 0) {
                break;
            }

            foreach ($items as $item) {
                try {
                    $name = trim((string) ($item['name'] ?? ''));
                    if ($this->isJunkName($name)) {
                        continue;
                    }

                    // Filtre strict produit par produit : rejeter tout câble réseau / télécoms
                    if ($this->isNetworkCable($name)) {
                        $totalSkippedNetwork++;
                        $this->line('  [⏭ Réseau ignoré] : ' . Str::limit($name, 55));
                        continue;
                    }

                    // Vérifier qu'il s'agit bien d'un câble électrique / incendie
                    if (!$this->isElectricalOrFireCable($name)) {
                        $this->line('  [⏭ Hors cible ignoré] : ' . Str::limit($name, 55));
                        continue;
                    }

                    $productUrl = $item['url'] ?? '';
                    $product = Product::where('sous_categorie_id', $targetSubCat->id)
                        ->where('name', $name)
                        ->first();

                    if ($product && !empty($product->photo) && !$force) {
                        continue;
                    }

                    $imageUrl = $item['imageUrl'] ?? $this->extractImageUrlFromProductPage($productUrl, $activeBaseUrl);
                    $localImage = $this->downloadImage($imageUrl, $name, $activeBaseUrl);

                    if ($product) {
                        $product->update([
                            'price' => null,
                            'photo' => $localImage ?? $product->photo,
                            'description' => 'Produit Électricité Générale — Câbles et Conducteurs / Sécurité Incendie (Sur devis).',
                        ]);
                        $totalUpdated++;
                        $this->line('  [🔄] Mis à jour (Sur devis) : ' . Str::limit($name, 45) . ' -> [' . $targetSubCat->nom . ']');
                    } else {
                        Product::create([
                            'sous_categorie_id' => $targetSubCat->id,
                            'name' => $name,
                            'price' => null,
                            'photo' => $localImage,
                            'description' => 'Produit Électricité Générale — Câbles et Conducteurs / Sécurité Incendie (Sur devis).',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $totalInserted++;
                        $this->line('  [✔] Inséré (Sur devis) : ' . Str::limit($name, 45) . ' -> [' . $targetSubCat->nom . ']');
                    }
                } catch (\Throwable $e) {
                    $this->error('  [X] Erreur produit : ' . $e->getMessage());
                }
            }

            $nextPage = $crawler->filter('a.next, li.next a, [rel="next"], .pagination a[href*="page="], .page-numbers a.next');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Insérés : {$totalInserted} | Mis à jour : {$totalUpdated} | Réseau ignorés : {$totalSkippedNetwork}");
        return self::SUCCESS;
    }

    /**
     * Exclut strictement les câbles réseau, télécoms, données et fibre.
     */
    private function isNetworkCable(string $name): bool
    {
        $lower = mb_strtolower($name);

        $networkPatterns = [
            '/\brj\s*45\b/i',
            '/\bcat\s*5e?\b/i',
            '/\bcat\s*6a?\b/i',
            '/\bcat\s*7a?\b/i',
            '/\bcat\s*8\b/i',
            '/\butp\b/i',
            '/\bftp\b/i',
            '/\bsftp\b/i',
            '/\bfftp\b/i',
            '/\bethernet\b/i',
            '/\bréseau\b/i',
            '/\breseau\b/i',
            '/\bfibre\s*optique\b/i',
            '/\bpatchcord\b/i',
            '/\bcordon\s*de\s*brassage\b/i',
            '/\bbrassage\b/i',
            '/\btéléphon/i',
            '/\btelephon/i',
            '/\bcoaxial\b/i',
            '/\bcoax\b/i',
            '/\bbus\s*(knx|rs485|can|profibus)\b/i',
            '/\bdata\b/i',
            '/\bcommunication\b/i',
        ];

        foreach ($networkPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Valide que le libellé correspond bien à un câble électrique / sécurité incendie.
     */
    private function isElectricalOrFireCable(string $name): bool
    {
        $lower = mb_strtolower($name);

        $electricPatterns = [
            '/\bcr1\b/i',
            '/\bc1\b/i',
            '/\bc2\b/i',
            '/\bincendie\b/i',
            '/\bsécurité\s*incendie\b/i',
            '/\bsecurite\s*incendie\b/i',
            '/\bssi\b/i',
            '/\bh07v-[ur]\b/i',
            '/\bh07rn-f\b/i',
            '/\bh05vv-f\b/i',
            '/\bu1000r2v\b/i',
            '/\bro2v\b/i',
            '/\brvfv\b/i',
            '/\bcâble\s*électrique\b/i',
            '/\bcable\s*electrique\b/i',
            '/\bconducteur\b/i',
            '/\b\d+\s*[gx]\s*\d+(?:[.,]\d+)?\b/i',
        ];

        foreach ($electricPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{status: int, body: string, blocked: bool, failed: bool}
     */
    private function fetchHtml(string $url): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/',
            ])
                ->withOptions(['verify' => false])
                ->connectTimeout(25)
                ->timeout(60)
                ->get($url);
        } catch (ConnectionException $e) {
            $this->warn('Erreur réseau : ' . $e->getMessage());
            return ['status' => 0, 'body' => '', 'blocked' => false, 'failed' => true];
        }

        $status = $response->status();
        $body = $response->body();
        $blocked = $status === 403 || str_contains($body, 'Just a moment...');

        return [
            'status' => $status,
            'body' => $body,
            'blocked' => $blocked,
            'failed' => $response->failed(),
        ];
    }

    private function extractItemsFromHtml(Crawler $crawler, string $baseUrl): array
    {
        $items = [];

        $selectors = [
            'article.product-miniature',
            '.product-miniature',
            'ul.products li.product',
            '.products .product',
            'li.type-product',
            '[data-id-product]',
            '[data-product-id]',
        ];

        foreach ($selectors as $selector) {
            $nodes = $crawler->filter($selector);
            if ($nodes->count() === 0) {
                continue;
            }

            foreach ($nodes as $domElement) {
                if (!$domElement instanceof \DOMElement) {
                    continue;
                }

                $node = new Crawler($domElement);
                $name = $this->extractProductName($node);
                if ($this->isJunkName($name)) {
                    continue;
                }

                $productUrl = $this->extractProductUrl($node, $baseUrl);
                $imageUrl = $this->extractImageUrl($node, $baseUrl);

                $items[$name] = [
                    'name' => $name,
                    'url' => $productUrl,
                    'imageUrl' => $imageUrl,
                ];
            }

            if (count($items) > 0) {
                return array_values($items);
            }
        }

        foreach ($crawler->filter('a[href*=".html"], a[href*="/product/"]') as $el) {
            if (!$el instanceof \DOMElement) {
                continue;
            }
            $name = trim((string) $el->textContent);
            if ($this->isJunkName($name)) {
                continue;
            }
            $href = trim((string) $el->getAttribute('href'));
            if (!isset($items[$name])) {
                $items[$name] = [
                    'name' => $name,
                    'url' => $this->normalizeUrl($href, $baseUrl),
                    'imageUrl' => null,
                ];
            }
        }

        return array_values($items);
    }

    private function extractProductName(Crawler $node): string
    {
        $titleSelectors = [
            '.product-title a',
            'h2.product-title a',
            '.product-title',
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
            'aperçu rapide',
            'apercu rapide',
            'en stock',
            'rupture de stock',
            'chauffage',
            'éclairage',
            'eclairage',
            'plomberie',
            'produits',
        ];

        foreach ($forbidden as $word) {
            if ($lower === $word || str_contains($lower, $word)) {
                return true;
            }
        }

        return false;
    }

    private function extractProductUrl(Crawler $node, string $baseUrl): string
    {
        $linkNode = $node->filter('.product-title a, h2.product-title a, a.woocommerce-LoopProduct-link, h2 a, h3 a, a');
        if ($linkNode->count() > 0) {
            return $this->normalizeUrl((string) $linkNode->first()->attr('href'), $baseUrl);
        }

        return '';
    }

    private function normalizeUrl(string $href, string $baseUrl): string
    {
        $href = trim($href);
        if ($href === '') {
            return '';
        }
        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }
        if (Str::startsWith($href, '//')) {
            return 'https:' . $href;
        }
        $parts = parse_url($baseUrl);
        $root = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'www.domomat.com');
        return $root . '/' . ltrim($href, '/');
    }

    private function extractImageUrl(Crawler $node, string $baseUrl): ?string
    {
        $imgNode = $node->filter('img');
        if ($imgNode->count() > 0) {
            $src = trim((string) (
                $imgNode->first()->attr('data-src')
                ?: $imgNode->first()->attr('data-lazy-src')
                ?: $imgNode->first()->attr('src')
            ));
            if ($src !== '' && !Str::contains($src, ['data:image', 'placeholder', 'logo'])) {
                return $this->normalizeUrl($src, $baseUrl);
            }
        }

        return null;
    }

    private function extractImageUrlFromProductPage(?string $productUrl, string $referer): ?string
    {
        if (empty($productUrl)) {
            return null;
        }

        $fetch = $this->fetchHtml($productUrl);
        if ($fetch['failed'] || $fetch['body'] === '') {
            return null;
        }

        $crawler = new Crawler($fetch['body']);
        $ogImage = $crawler->filter('meta[property="og:image"]');
        if ($ogImage->count() > 0) {
            $url = trim((string) $ogImage->first()->attr('content'));
            return $url !== '' ? $this->normalizeUrl($url, $referer) : null;
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
}
