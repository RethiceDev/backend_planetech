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

class ScrapeEtechnologiesServeursStockageCommand extends Command
{
    protected $signature = 'scrape:etechnologies-serveurs-stockage
                            {url=https://etechnologies.shop/product-category/serveurs-stockage/ : URL de catégorie à scraper}
                            {--force : Mettre à jour même si la photo existe déjà}';

    protected $description = 'Scrape Serveurs & Stockage E-Technologies SHOP et range dans Informatique et Bureautique ou Réseaux & Télécoms';

    public function handle(): int
    {
        $rawUrl = trim((string) $this->argument('url'));
        $baseUrl = rtrim(preg_replace('#^http://#i', 'https://', $rawUrl), '/');
        $force = (bool) $this->option('force');

        $this->info("Début du scraping sur {$baseUrl}/ ...");

        $informatique = Category::firstOrCreate(
            ['nom' => 'Informatique et Bureautique'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $reseaux = Category::firstOrCreate(
            ['nom' => 'Réseaux & Télécoms'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $infoSousCatsList = [
            'Ordinateurs Portables et PC de Bureau',
            'Écrans et Périphériques',
            'Composants Informatiques',
            'Impression et Consommables',
            'Stockage et Sauvegarde',
            'Accessoires et Connectique PC',
            'Logiciels et Licences',
            'Alimentation et Onduleurs',
        ];

        $reseauxSousCatsList = [
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

        $infoSousCats = [];
        foreach ($infoSousCatsList as $nomSub) {
            $infoSousCats[$nomSub] = SousCategorie::firstOrCreate(
                [
                    'category_id' => $informatique->id,
                    'nom' => $nomSub,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $reseauxSousCats = [];
        foreach ($reseauxSousCatsList as $nomSub) {
            $reseauxSousCats[$nomSub] = SousCategorie::firstOrCreate(
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
                    [$cat, $subCat] = $this->resolveCategoryAndSousCategorie(
                        $name,
                        $productUrl,
                        $informatique,
                        $infoSousCats,
                        $reseaux,
                        $reseauxSousCats
                    );

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
                        $this->line('  [🔄] Mis à jour : ' . Str::limit($name, 42) . ' -> [' . $cat->nom . ' > ' . $subCat->nom . ']');
                    } else {
                        Product::create([
                            'sous_categorie_id' => $subCat->id,
                            'name' => $name,
                            'price' => $price,
                            'photo' => $localImage,
                            'description' => 'Produit Serveurs & Stockage — E-Technologies SHOP.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $totalInserted++;
                        $this->line('  [✔] Inséré : ' . Str::limit($name, 42) . ' -> [' . $cat->nom . ' > ' . $subCat->nom . ']');
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

    private function resolveCategoryAndSousCategorie(
        string $name,
        string $productUrl,
        Category $informatique,
        array $infoSousCats,
        Category $reseaux,
        array $reseauxSousCats
    ): array {
        $text = mb_strtolower($name . ' ' . $productUrl);

        if (
            str_contains($text, 'baie de brassage') ||
            str_contains($text, 'coffret de brassage') ||
            str_contains($text, 'panneau de brassage')
        ) {
            return [$reseaux, $reseauxSousCats['Baies et Coffrets de Brassage']];
        }

        if (
            str_contains($text, 'switch') ||
            str_contains($text, 'routeur') ||
            str_contains($text, 'wi-fi') ||
            str_contains($text, 'wifi') ||
            str_contains($text, 'access point')
        ) {
            return [$reseaux, $reseauxSousCats['Équipements Actifs (Switchs, Routeurs, Wi-Fi)']];
        }

        if (
            str_contains($text, 'onduleur') ||
            str_contains($text, 'ups') ||
            str_contains($text, 'alimentation') ||
            str_contains($text, 'psu')
        ) {
            return [$informatique, $infoSousCats['Alimentation et Onduleurs']];
        }

        if (
            str_contains($text, 'nas') ||
            str_contains($text, 'san') ||
            str_contains($text, 'stockage') ||
            str_contains($text, 'sauvegarde') ||
            str_contains($text, 'disque dur') ||
            str_contains($text, 'hdd') ||
            str_contains($text, 'ssd') ||
            str_contains($text, 'nvme') ||
            str_contains($text, 'clé usb') ||
            str_contains($text, 'cle usb') ||
            str_contains($text, 'cartouche') ||
            str_contains($text, 'lto') ||
            str_contains($text, 'raid')
        ) {
            return [$informatique, $infoSousCats['Stockage et Sauvegarde']];
        }

        if (
            str_contains($text, 'serveur') ||
            str_contains($text, 'server') ||
            str_contains($text, 'ram') ||
            str_contains($text, 'mémoire') ||
            str_contains($text, 'memoire') ||
            str_contains($text, 'processeur') ||
            str_contains($text, 'xeon') ||
            str_contains($text, 'carte mère') ||
            str_contains($text, 'carte mere') ||
            str_contains($text, 'contrôleur') ||
            str_contains($text, 'controleur') ||
            str_contains($text, 'châssis') ||
            str_contains($text, 'chassis') ||
            str_contains($text, 'rackmount')
        ) {
            return [$informatique, $infoSousCats['Composants Informatiques']];
        }

        if (
            str_contains($text, 'câble sata') ||
            str_contains($text, 'cable sata') ||
            str_contains($text, 'sas') ||
            str_contains($text, 'adaptateur') ||
            str_contains($text, 'cordon')
        ) {
            return [$informatique, $infoSousCats['Accessoires et Connectique PC']];
        }

        return [$informatique, $infoSousCats['Stockage et Sauvegarde']];
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