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

class ScrapeDomomatDisjoncteurDifferentielCommand extends Command
{
    protected $signature = 'scrape:domomat-disjoncteur-differentiel
                            {url? : URL à scraper}
                            {--file= : Fichier HTML local dans storage/app (ex: domomat-2062.html)}';

    protected $description = 'Scrape les disjoncteurs différentiels dans Électricité Générale > Matériel BT (Basse Tension)';

    public function handle(): int
    {
        $baseUrl = (string) ($this->argument('url') ?: 'https://www.domomat.com/2062-disjoncteur-differentiel');
        $localFile = (string) $this->option('file');

        $this->info("Début du scraping sur {$baseUrl}...");

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

        // Priorité absolue au fichier HTML local si --file est passé
        if ($localFile !== '') {
            if (!Storage::disk('local')->exists($localFile)) {
                $this->error("Fichier introuvable : storage/app/{$localFile}");
                return self::FAILURE;
            }

            $this->info(" -> Lecture du fichier local : storage/app/{$localFile}");
            $html = Storage::disk('local')->get($localFile);

            if (str_contains($html, 'Just a moment...')) {
                $this->warn(' -> Le fichier HTML contient la page Cloudflare (Just a moment...). Sauvegardez la page une fois affichée dans Chrome.');
                return self::SUCCESS;
            }

            [$processed, $updated] = $this->processCrawler(new Crawler($html), $baseUrl, $sousCategories);
            $this->info("\nTerminé ! Traité : {$processed} | Insérés/mis à jour : {$updated}");
            return self::SUCCESS;
        }

        $totalProcessed = 0;
        $totalUpdated = 0;

        for ($page = 1; $page <= 20; $page++) {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';
            $url = $page === 1 ? $baseUrl : $baseUrl . $separator . 'page=' . $page;
            $this->info("\n--- Page {$page} : {$url} ---");

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                ])
                    ->withOptions(['verify' => false])
                    ->connectTimeout(30)
                    ->timeout(60)
                    ->get($url);
            } catch (ConnectionException $e) {
                $this->warn(" -> Erreur réseau page {$page} : " . $e->getMessage());
                sleep(3);
                continue;
            }

            $status = $response->status();
            $body = $response->body();

            if ($status === 403 || str_contains($body, 'Just a moment...')) {
                $this->warn(" -> Blocage Cloudflare (403) sur {$url}. Enregistrez la page depuis Chrome dans storage/app/domomat-2062.html puis relancez avec --file=domomat-2062.html.");
                break;
            }

            if ($status === 404) {
                $this->warn(" -> Page introuvable (404) : {$url}");
                break;
            }

            if ($response->failed()) {
                $this->warn(" -> Erreur HTTP ({$status}).");
                break;
            }

            $crawler = new Crawler($body);
            $nodes = $crawler->filter('.product-miniature, article.product-miniature, .product-item, li.item.product, [data-product-id]');
            $this->info(' -> Produits détectés : ' . $nodes->count());

            if ($nodes->count() === 0) {
                break;
            }

            [$processed, $updated] = $this->processCrawler($crawler, $baseUrl, $sousCategories);
            $totalProcessed += $processed;
            $totalUpdated += $updated;

            $nextPage = $crawler->filter('a.next, .pagination .next a, .pages-item-next a, [rel="next"]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Traité : {$totalProcessed} | Insérés/mis à jour : {$totalUpdated}");
        return self::SUCCESS;
    }

    private function processCrawler(Crawler $crawler, string $baseUrl, array $sousCategories): array
    {
        $nodes = $crawler->filter('.product-miniature, article.product-miniature, .product-item, li.item.product, [data-product-id]');
        $this->info(' -> Produits détectés : ' . $nodes->count());

        $processed = 0;
        $updated = 0;

        foreach ($nodes as $domElement) {
            try {
                $node = new Crawler($domElement);
                $name = '';
                $productUrl = '';

                $linkNode = $node->filter('h2 a, h3 a, .product-title a, a.product-thumbnail, a.product-item-photo, a[href*=".html"]');
                if ($linkNode->count() > 0) {
                    $productUrl = trim((string) $linkNode->attr('href'));
                    if ($productUrl !== '' && !Str::startsWith($productUrl, ['http://', 'https://'])) {
                        $host = str_contains($baseUrl, 'domomat.com') ? 'https://www.domomat.com/' : 'https://www.123elec.com/';
                        $productUrl = $host . ltrim($productUrl, '/');
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

                $product = Product::where('sous_categorie_id', $subCat->id)
                    ->where('name', $name)
                    ->first();

                if ($product && !empty($product->photo)) {
                    $processed++;
                    continue;
                }

                $imageUrl = $this->extractOgImage($productUrl, $baseUrl);
                $localImagePath = $this->downloadImage($imageUrl, $name, $productUrl);
                $price = $this->extractPrice($node);

                if ($product) {
                    $product->update([
                        'price' => $price ?? $product->price,
                        'photo' => $localImagePath ?? $product->photo,
                    ]);
                    $updated++;
                    $this->line('    [🔄] Mis à jour : ' . Str::limit($name, 45) . ' -> [' . $subCat->nom . ']');
                } else {
                    Product::create([
                        'sous_categorie_id' => $subCat->id,
                        'name' => $name,
                        'price' => $price,
                        'photo' => $localImagePath,
                        'description' => 'Disjoncteur différentiel professionnel.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $updated++;
                    $this->line('    [✔] Inséré : ' . Str::limit($name, 45) . ' -> [' . $subCat->nom . ']');
                }

                $processed++;
            } catch (\Throwable $e) {
                $this->error('    [X] Erreur produit : ' . $e->getMessage());
            }
        }

        return [$processed, $updated];
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
                ->timeout(30)
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

    private function downloadImage(?string $imageUrl, string $name, ?string $referer): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Referer' => $referer ?: 'https://www.domomat.com/',
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
        $priceElements = $node->filter('.price, .product-price, span[itemprop="price"], span[data-price-amount]');
        $priceText = $priceElements->count() > 0 ? $priceElements->text() : $node->text();

        if (preg_match('/([\d\s,\.]+)\s*€/', $priceText, $matches)) {
            $cleanPrice = str_replace([' ', ','], ['', '.'], trim($matches[1]));
            return is_numeric($cleanPrice) ? (float) $cleanPrice : null;
        }

        return null;
    }
}