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

class ScrapeEtechnologiesReseauxCommand extends Command
{
    protected $signature = 'scrape:etechnologies-reseaux
                            {url=https://etechnologies.shop/product-category/cablage/fibres-optiques/ : URL de catégorie à scraper}
                            {--force : Mettre à jour même si la photo existe déjà}';

    protected $description = 'Scrape E-Technologies SHOP et range les produits dans Réseaux & Télécoms';

    public function handle(): int
    {
        $baseUrl = rtrim((string) $this->argument('url'), '/');
        $force = (bool) $this->option('force');
        $this->info("Début du scraping sur {$baseUrl}/ ...");

        // 1. Catégorie principale
        $reseaux = Category::firstOrCreate(
            ['nom' => 'Réseaux & Télécoms'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // 2. Sous-catégories associées
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
                    $subCat = $this->resolveSousCategorie($name, $baseUrl, $sousCategories);

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
                            'description' => 'Produit Réseaux & Télécoms — E-Technologies SHOP.',
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

    private function extractProductNodes(Crawler $crawler): Crawler
    {
        $selectors = [
            'ul.products li.product',
            '.products .product',
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

    private function resolveSousCategorie(string $name, string $baseUrl, array $sousCategories): SousCategorie
    {
        $lower = mb_strtolower($name . ' ' . $baseUrl);

        if (
            str_contains($lower, 'fibre') ||
            str_contains($lower, 'optique') ||
            str_contains($lower, 'touret') ||
            str_contains($lower, 'brin') ||
            str_contains($lower, 'sfp') ||
            str_contains($lower, 'jarreti')
        ) {
            return $sousCategories['Fibre Optique'];
        }

        if (
            str_contains($lower, 'switch') ||
            str_contains($lower, 'routeur') ||
            str_contains($lower, 'wi-fi') ||
            str_contains($lower, 'wifi') ||
            str_contains($lower, 'access point') ||
            str_contains($lower, 'point d\'accès') ||
            str_contains($lower, 'firewall')
        ) {
            return $sousCategories['Équipements Actifs (Switchs, Routeurs, Wi-Fi)'];
        }

        if (
            str_contains($lower, 'baie') ||
            str_contains($lower, 'coffret') ||
            str_contains($lower, 'rack') ||
            str_contains($lower, 'armoire')
        ) {
            return $sousCategories['Baies et Coffrets de Brassage'];
        }

        if (
            str_contains($lower, 'prise') ||
            str_contains($lower, 'connecteur') ||
            str_contains($lower, 'rj45') ||
            str_contains($lower, 'brassage') ||
            str_contains($lower, 'pigtail') ||
            str_contains($lower, 'coupleur') ||
            str_contains($lower, 'adaptateur')
        ) {
            return $sousCategories['Connectique, Prises et Brassage'];
        }

        if (
            str_contains($lower, 'téléphon') ||
            str_contains($lower, 'telephon') ||
            str_contains($lower, 'interphone') ||
            str_contains($lower, 'voip')
        ) {
            return $sousCategories['Téléphonie et Communication'];
        }

        if (
            str_contains($lower, 'antenne') ||
            str_contains($lower, 'satellite') ||
            str_contains($lower, 'tnt') ||
            str_contains($lower, 'parabole')
        ) {
            return $sousCategories['Antennes, TV et Satellite'];
        }

        if (
            str_contains($lower, 'caméra') ||
            str_contains($lower, 'camera') ||
            str_contains($lower, 'vidéosurveillance') ||
            str_contains($lower, 'videosurveillance') ||
            str_contains($lower, 'nvr') ||
            str_contains($lower, 'dvr')
        ) {
            return $sousCategories['Vidéosurveillance IP'];
        }

        if (
            str_contains($lower, 'testeur') ||
            str_contains($lower, 'sertisseuse') ||
            str_contains($lower, 'dénudeuse') ||
            str_contains($lower, 'réflectomètre') ||
            str_contains($lower, 'mesure') ||
            str_contains($lower, 'outillage')
        ) {
            return $sousCategories['Outillage et Mesure Réseau'];
        }

        if (
            str_contains($lower, 'câble') ||
            str_contains($lower, 'cable') ||
            str_contains($lower, 'cat6') ||
            str_contains($lower, 'cat5') ||
            str_contains($lower, 'cat7') ||
            str_contains($lower, 'cordon')
        ) {
            return $sousCategories['Câbles Réseau et Télécoms'];
        }

        return $sousCategories['Fibre Optique'];
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
