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

class ScrapeDisjoncteursBtCommand extends Command
{
    protected $signature = 'scrape:disjoncteurs-bt
                            {url=https://www.123elec.com/tableau-electrique/disjoncteur-differentiel.html : URL de catégorie à scraper}
                            {--file= : Fichier HTML local dans storage/app en option}';

    protected $description = 'Scrape les disjoncteurs différentiels et les range dans Électricité Générale';

    public function handle(): int
    {
        $baseUrl = (string) $this->argument('url');
        $localFile = (string) $this->option('file');

        $electricite = Category::firstOrCreate(
            ['nom' => 'Électricité Générale'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $sousCategoriesList = [
            'Matériel HT (Haute Tension) / MT (Moyenne Tension)',
            'Matériel BT (Basse Tension)',
            'Câbles et Conducteurs',
            'Transformateurs',
            'Appareillage et Accessoires Domestiques / Tertiaires',
            'Accessoires de Pose, Fixation et Raccordement',
        ];

        $sousCategories = [];
        foreach ($sousCategoriesList as $nomSub) {
            $sousCategories[$nomSub] = SousCategorie::firstOrCreate(
                [
                    'category_id' => $electricite->id,
                    'nom' => $nomSub,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        if ($localFile !== '') {
            if (!Storage::disk('local')->exists($localFile)) {
                $this->error("Fichier introuvable : storage/app/{$localFile}");
                return self::FAILURE;
            }

            $html = Storage::disk('local')->get($localFile);
            $inserted = $this->parseAndStore(new Crawler($html), $baseUrl, $sousCategories);
            $this->info("Terminé depuis fichier local. Produits insérés : {$inserted}");
            return self::SUCCESS;
        }

        $totalInserted = 0;

        for ($page = 1; $page <= 10; $page++) {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';
            $url = $page === 1 ? $baseUrl : $baseUrl . $separator . 'p=' . $page;
            $this->info("--- Page {$page} : {$url} ---");

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                ])
                    ->withOptions(['verify' => false])
                    ->connectTimeout(20)
                    ->timeout(45)
                    ->get($url);
            } catch (ConnectionException $e) {
                $this->warn('Erreur réseau : ' . $e->getMessage());
                break;
            }

            if ($response->status() === 403 || str_contains($response->body(), 'Just a moment...')) {
                $this->warn("Blocage anti-bot (403) sur {$url}.");
                break;
            }

            if ($response->failed()) {
                $this->warn('Erreur HTTP : ' . $response->status());
                break;
            }

            $crawler = new Crawler($response->body());
            $nodes = $crawler->filter('.product-item, li.item.product, .product-miniature, article.product-miniature, [data-product-id]');
            $this->info('Produits détectés : ' . $nodes->count());

            if ($nodes->count() === 0) {
                break;
            }

            $totalInserted += $this->parseAndStore($crawler, $baseUrl, $sousCategories);

            $nextPage = $crawler->filter('.pages-item-next a, a.action.next, a.next, [rel="next"]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("Terminé ! Total insérés : {$totalInserted}");
        return self::SUCCESS;
    }

    private function parseAndStore(Crawler $crawler, string $baseUrl, array $sousCategories): int
    {
        $nodes = $crawler->filter('.product-item, li.item.product, .product-miniature, article.product-miniature, [data-product-id]');
        $inserted = 0;

        foreach ($nodes as $domElement) {
            $node = new Crawler($domElement);
            $name = '';
            $productUrl = '';

            $linkNode = $node->filter('a.product-item-photo, a.product.photo, h2 a, h3 a, .product-title a, a[href*=".html"]');
            if ($linkNode->count() > 0) {
                $productUrl = trim((string) $linkNode->attr('href'));
                if ($productUrl !== '' && !Str::startsWith($productUrl, ['http://', 'https://'])) {
                    $parsed = parse_url($baseUrl);
                    $origin = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'www.123elec.com');
                    $productUrl = $origin . '/' . ltrim($productUrl, '/');
                }
            }

            foreach (['h1', 'h2', 'h3', 'strong', 'a'] as $tag) {
                $filtered = $node->filter($tag);
                if ($filtered->count() > 0) {
                    foreach ($filtered as $el) {
                        $txt = trim($el->textContent);
                        $lower = mb_strtolower($txt);
                        if (
                            mb_strlen($txt) > 10 &&
                            !str_contains($lower, 'ajouter') &&
                            !str_contains($lower, 'stock') &&
                            !str_contains($lower, 'voir le produit')
                        ) {
                            $name = $txt;
                            break 2;
                        }
                    }
                }
            }

            if ($name === '') {
                continue;
            }

            $subCat = $this->resolveSousCategorie($name, $sousCategories);

            $exists = Product::where('sous_categorie_id', $subCat->id)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            $imageUrl = $this->extractOgImage($productUrl, $baseUrl);
            $localImage = $this->downloadImage($imageUrl, $name, $baseUrl);
            $price = $this->extractPrice($node);

            Product::create([
                'sous_categorie_id' => $subCat->id,
                'name' => $name,
                'price' => $price,
                'photo' => $localImage,
                'description' => 'Disjoncteur différentiel professionnel.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inserted++;
            $this->line("  [✔] {$name} -> [{$subCat->nom}]");
        }

        return $inserted;
    }

    private function resolveSousCategorie(string $name, array $sousCategories): SousCategorie
    {
        $lower = mb_strtolower($name);

        if (
            str_contains($lower, 'hta') ||
            str_contains($lower, 'moyenne tension') ||
            str_contains($lower, 'haute tension') ||
            str_contains($lower, ' kv')
        ) {
            return $sousCategories['Matériel HT (Haute Tension) / MT (Moyenne Tension)'];
        }

        if (
            str_contains($lower, 'peigne') ||
            str_contains($lower, 'borne') ||
            str_contains($lower, 'embout') ||
            str_contains($lower, 'rail din') ||
            str_contains($lower, 'cache-borne')
        ) {
            return $sousCategories['Accessoires de Pose, Fixation et Raccordement'];
        }

        return $sousCategories['Matériel BT (Basse Tension)'];
    }

    private function extractOgImage(?string $productUrl, string $referer): ?string
    {
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

            if ($response->successful() && !str_contains($response->body(), 'Just a moment...')) {
                $crawler = new Crawler($response->body());
                $ogImage = $crawler->filter('meta[property="og:image"]');
                if ($ogImage->count() > 0) {
                    $url = trim((string) $ogImage->attr('content'));
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
                ->timeout(25)
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
        $priceElements = $node->filter('.price, .product-price, span[itemprop="price"], span[data-price-amount]');
        $priceText = $priceElements->count() > 0 ? $priceElements->text() : $node->text();

        if (preg_match('/([\d\s,\.]+)\s*€/', $priceText, $matches)) {
            $cleanPrice = str_replace([' ', ','], ['', '.'], trim($matches[1]));
            return is_numeric($cleanPrice) ? (float) $cleanPrice : null;
        }

        return null;
    }
}