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

class Scrape123ElecFroidClimatisationCommand extends Command
{
    protected $signature = 'scrape:123elec-froid-climatisation {url? : URL de catégorie 123elec à scraper}';
    protected $description = 'Scrape Froid & Climatisation depuis 123elec avec répartition dans les sous-catégories';

    public function handle(): int
    {
        $baseUrl = (string) ($this->argument('url') ?: 'https://www.123elec.com/vmc-hottes-aeration.html');
        $this->info("Début du scraping Froid & Climatisation sur {$baseUrl}...");

        // 1. Créer ou récupérer la catégorie principale
        $categorie = Category::firstOrCreate(
            ['nom' => 'Froid & Climatisation'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // 2. Liste des sous-catégories associées et création sécurisée
        $nomsSousCategories = [
            'Climatiseurs Split',
            'Climatiseurs Armoires',
            'Climatiseurs Cassettes',
            'Chambres Froides (Positives & Négatives)',
            'Compresseurs Frigorifiques',
            'Tubes et Isolants Cuivre',
            'Gaz Réfrigérants (R410A, R32, R134a)',
            'Pièces Détachées & Accessoires',
        ];

        $sousCategories = [];
        foreach ($nomsSousCategories as $nomSub) {
            $sousCategories[$nomSub] = SousCategorie::firstOrCreate(
                [
                    'category_id' => $categorie->id,
                    'nom' => $nomSub,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $totalProcessed = 0;
        $totalUpdated = 0;

        for ($page = 1; $page <= 20; $page++) {
            $url = $page === 1 ? $baseUrl : $baseUrl . '?p=' . $page;
            $this->info("\n--- Page {$page} : {$url} ---");

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                    ->withOptions(['verify' => false])
                    ->connectTimeout(30)
                    ->timeout(60)
                    ->retry(3, 3000)
                    ->get($url);
            } catch (ConnectionException $e) {
                $this->warn(" -> Erreur réseau/DNS sur la page {$page} : " . $e->getMessage());
                sleep(3);
                continue;
            }

            if ($response->status() === 404) {
                $this->warn(" -> Page introuvable (404) : {$url}");
                break;
            }

            if ($response->failed()) {
                $this->warn(' -> Erreur HTTP (Code : ' . $response->status() . ').');
                break;
            }

            $crawler = new Crawler($response->body());
            $nodes = $crawler->filter('.product-item, li.item.product, [data-product-id]');
            $this->info(' -> Produits détectés dans le HTML : ' . $nodes->count());

            if ($nodes->count() === 0) {
                $this->line(' -> Plus de produits détectés, fin de la pagination.');
                break;
            }

            foreach ($nodes as $domElement) {
                try {
                    $node = new Crawler($domElement);

                    $name = '';
                    $productUrl = '';

                    $linkNode = $node->filter('a.product-item-photo, a.product.photo, a[href*=".html"]');
                    if ($linkNode->count() > 0) {
                        $productUrl = trim((string) $linkNode->attr('href'));
                        if ($productUrl !== '' && !Str::startsWith($productUrl, ['http://', 'https://'])) {
                            $productUrl = 'https://www.123elec.com/' . ltrim($productUrl, '/');
                        }
                    }

                    foreach (['h1', 'h2', 'h3', 'strong', 'a'] as $tag) {
                        $filtered = $node->filter($tag);
                        if ($filtered->count() > 0) {
                            foreach ($filtered as $el) {
                                $txt = trim($el->textContent);
                                $lowerTxt = mb_strtolower($txt);
                                if (
                                    mb_strlen($txt) > 10 &&
                                    !str_contains($lowerTxt, 'ajouter') &&
                                    !str_contains($lowerTxt, 'stock')
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

                    $assignedSubCat = $this->resolveSousCategorie($name, $sousCategories);

                    $product = Product::where('sous_categorie_id', $assignedSubCat->id)
                        ->where('name', $name)
                        ->first();

                    if ($product && !empty($product->photo)) {
                        $totalProcessed++;
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
                        $totalUpdated++;
                        $this->line('    [🔄] Mis à jour : ' . Str::limit($name, 40) . ' [' . $assignedSubCat->nom . ']');
                    } else {
                        Product::create([
                            'sous_categorie_id' => $assignedSubCat->id,
                            'name' => $name,
                            'price' => $price,
                            'photo' => $localImagePath,
                            'description' => 'Équipement froid, climatisation et ventilation disponible sur 123elec.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $totalUpdated++;
                        $this->line('    [✔] Inséré : ' . Str::limit($name, 40) . ' [' . $assignedSubCat->nom . ']');
                    }

                    $totalProcessed++;
                } catch (\Throwable $e) {
                    $this->error('    [X] Erreur produit : ' . $e->getMessage());
                }
            }

            $nextPage = $crawler->filter('.pages-item-next a, a.action.next, [rel="next"]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Total traité : {$totalProcessed} | Total mis à jour/insérés : {$totalUpdated}");

        return self::SUCCESS;
    }

    private function resolveSousCategorie(string $name, array $sousCategories): SousCategorie
    {
        $lowerName = mb_strtolower($name);

        if (str_contains($lowerName, 'split')) {
            return $sousCategories['Climatiseurs Split'];
        }
        if (str_contains($lowerName, 'armoire')) {
            return $sousCategories['Climatiseurs Armoires'];
        }
        if (str_contains($lowerName, 'cassette')) {
            return $sousCategories['Climatiseurs Cassettes'];
        }
        if (str_contains($lowerName, 'chambre froide') || str_contains($lowerName, 'frigorifique')) {
            return $sousCategories['Chambres Froides (Positives & Négatives)'];
        }
        if (str_contains($lowerName, 'compresseur')) {
            return $sousCategories['Compresseurs Frigorifiques'];
        }
        if (str_contains($lowerName, 'cuivre') || str_contains($lowerName, 'isolant') || str_contains($lowerName, 'tube')) {
            return $sousCategories['Tubes et Isolants Cuivre'];
        }
        if (str_contains($lowerName, 'r410a') || str_contains($lowerName, 'r32') || str_contains($lowerName, 'r134a') || str_contains($lowerName, 'gaz') || str_contains($lowerName, 'réfrigérant') || str_contains($lowerName, 'refrigerant')) {
            return $sousCategories['Gaz Réfrigérants (R410A, R32, R134a)'];
        }

        return $sousCategories['Pièces Détachées & Accessoires'];
    }

    private function extractOgImage(?string $productUrl, string $referer): ?string
    {
        if (empty($productUrl)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Referer' => $referer,
            ])
                ->withOptions(['verify' => false])
                ->connectTimeout(15)
                ->timeout(30)
                ->retry(2, 2000)
                ->get($productUrl);

            if ($response->successful()) {
                $crawler = new Crawler($response->body());
                $ogImage = $crawler->filter('meta[property="og:image"]');
                if ($ogImage->count() > 0) {
                    $url = trim((string) $ogImage->attr('content'));
                    return str_starts_with($url, '//') ? 'https:' . $url : $url;
                }
            }
        } catch (\Throwable $e) {
            // Ignorer l'échec individuel de fiche produit
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
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Referer' => $referer ?: 'https://www.123elec.com/',
            ])
                ->withOptions(['verify' => false])
                ->connectTimeout(15)
                ->timeout(30)
                ->retry(2, 2000)
                ->get($imageUrl);

            if ($response->successful() && !empty($response->body())) {
                $imageName = Str::slug(Str::limit($name, 40, '')) . '-' . time() . '-' . random_int(100, 999) . '.jpg';
                Storage::disk('public')->put('products/' . $imageName, $response->body());

                return 'storage/products/' . $imageName;
            }
        } catch (\Throwable $e) {
            // Ignorer l'échec individuel de téléchargement image
        }

        return null;
    }

    private function extractPrice(Crawler $node): ?float
    {
        $priceElements = $node->filter('.price, span[data-price-amount], .price-wrapper, .normal-price .price');
        $priceText = $priceElements->count() > 0 ? $priceElements->text() : $node->text();

        if (preg_match('/([\d\s,\.]+)\s*€/', $priceText, $matches)) {
            $cleanPrice = str_replace([' ', ','], ['', '.'], trim($matches[1]));
            return is_numeric($cleanPrice) ? (float) $cleanPrice : null;
        }

        return null;
    }
}
