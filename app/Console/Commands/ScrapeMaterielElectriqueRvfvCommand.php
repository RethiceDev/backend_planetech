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

class ScrapeMaterielElectriqueRvfvCommand extends Command
{
    protected $signature = 'scrape:materiel-rvfv
                            {url=https://www.materielelectrique.com/c/cable-electrique-rigide-edf-rvfv-6407 : URL cible à scraper}
                            {--force : Mettre à jour même si la photo existe déjà}';

    protected $description = 'Scrape les câbles électriques rigides EDF RVFV (sur devis, sans prix) vers Électricité Générale > Câbles et Conducteurs';

    public function handle(): int
    {
        $rawUrl = trim((string) $this->argument('url'));
        $baseUrl = rtrim(preg_replace('#^http://#i', 'https://', $rawUrl), '/');
        $force = (bool) $this->option('force');

        $this->info("Début du scraping sur {$baseUrl} ...");

        // 1. Récupération ou création sécurisée de la Catégorie Principale
        $electricite = Category::firstOrCreate(
            ['nom' => 'Électricité Générale'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // 2. Gestion sécurisée des Sous-catégories associées
        $sousCategories = [
            'Matériel HT (Haute Tension) / MT (Moyenne Tension)',
            'Matériel BT (Basse Tension)',
            'Câbles et Conducteurs',
            'Transformateurs',
            'Appareillage et Accessoires Domestiques / Tertiaires',
            'Accessoires de Pose, Fixation et Raccordement',
        ];

        $subMap = [];
        foreach ($sousCategories as $nomSub) {
            $subMap[$nomSub] = SousCategorie::firstOrCreate(
                [
                    'category_id' => $electricite->id,
                    'nom' => $nomSub,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $targetSubCat = $subMap['Câbles et Conducteurs'];

        $totalInserted = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        for ($page = 1; $page <= 20; $page++) {
            $url = $page === 1
                ? $baseUrl
                : $baseUrl . (str_contains($baseUrl, '?') ? '&page=' . $page : '?page=' . $page);

            $this->info("\n--- Page {$page} : {$url} ---");

            $fetch = $this->fetchHtml($url);

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
            $items = $this->extractItemsFromHtml($crawler, $baseUrl);
            $this->info('Éléments détectés : ' . count($items) . ' (HTTP ' . $fetch['status'] . ')');

            if (count($items) === 0) {
                break;
            }

            foreach ($items as $item) {
                try {
                    $name = trim((string) ($item['name'] ?? ''));
                    if ($this->isJunkName($name) || $this->isNetworkOrNonCable($name)) {
                        $totalSkipped++;
                        continue;
                    }

                    $productUrl = $item['url'] ?? '';
                    $product = Product::where('sous_categorie_id', $targetSubCat->id)
                        ->where('name', $name)
                        ->first();

                    if ($product && !empty($product->photo) && !$force) {
                        continue;
                    }

                    $imageUrl = $item['imageUrl'] ?? $this->extractImageUrlFromProductPage($productUrl, $baseUrl);
                    $localImage = $this->downloadImage($imageUrl, $name, $baseUrl);

                    $desc = 'Produit Électricité Générale — Câbles et Conducteurs (Sur devis).';

                    if ($product) {
                        $product->update([
                            'price' => null,
                            'photo' => $localImage ?? $product->photo,
                            'description' => $desc,
                        ]);
                        $totalUpdated++;
                        $this->line('  [🔄] Mis à jour (Sur devis) : ' . Str::limit($name, 50));
                    } else {
                        Product::create([
                            'sous_categorie_id' => $targetSubCat->id,
                            'name' => $name,
                            'price' => null,
                            'photo' => $localImage,
                            'description' => $desc,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $totalInserted++;
                        $this->line('  [✔] Inséré (Sur devis) : ' . Str::limit($name, 50));
                    }
                } catch (\Throwable $e) {
                    $this->error('  [X] Erreur produit : ' . $e->getMessage());
                }
            }

            $nextPage = $crawler->filter('a.next, li.next a, [rel="next"], .pagination a[href*="page="]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Insérés : {$totalInserted} | Mis à jour : {$totalUpdated} | Ignorés : {$totalSkipped}");
        return self::SUCCESS;
    }

    private function isNetworkOrNonCable(string $name): bool
    {
        $lower = mb_strtolower($name);

        if (preg_match('/\b(rj\s*45|cat\s*[5678]e?|utp|ftp|sftp|ethernet|fibre|optique|brassage|switch|routeur|wi-?fi)\b/i', $lower)) {
            return true;
        }

        return !preg_match('/\b(rvfv|edf|n1x6g3|fr-?n1x6g3|r2v|u-?1000\s*r2v|ro2v|h07v-[ur]|h07vu|h07vr|h05v-[ku]|h05vk|h05vv-?f|h07rn-?f|fil|câble|cable|conducteur|mm2|\d+\s*[gx]\s*\d+)/i', $lower);
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
                'Referer' => 'https://www.materielelectrique.com/',
            ])
                ->withOptions(['verify' => false])
                ->connectTimeout(25)
                ->timeout(60)
                ->retry(2, 2000)
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
            '.product-card',
            '.product-item',
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

        foreach ($crawler->filter('a[href*="/p/"], a[href*=".html"], a[href*="/product/"]') as $el) {
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
            'filtrer',
            'trier par',
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
        $root = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'www.materielelectrique.com');
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
