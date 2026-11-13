<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\SousCategorie;
use App\Models\Product;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Scrape123ElecColliersFixationCommand extends Command
{
    protected $signature = 'scrape:123elec-colliers-fixation';
    protected $description = 'Scrape les colliers et accessoires de fixation depuis 123elec dans Accessoires de Pose avec gestion des images og:image';

    public function handle()
    {
        $this->info("Début du scraping des colliers et accessoires de fixation sur 123elec...");

        // 1. Récupération ou création sécurisée de la Catégorie Principale
        $electricite = Category::firstOrCreate(
            ['nom' => 'Électricité Générale'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // 2. Attribution ciblée dans la sous-catégorie Accessoires de Pose, Fixation et Raccordement
        $sousCategorie = SousCategorie::firstOrCreate(
            [
                'category_id' => $electricite->id,
                'nom' => 'Accessoires de Pose, Fixation et Raccordement'
            ],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $baseUrl = 'https://www.123elec.com/cables-gaines-conduits/colliers-fixation.html';
        $totalProcessed = 0;
        $totalUpdated = 0;

        for ($page = 1; $page <= 10; $page++) {
            $url = $page === 1 ? $baseUrl : $baseUrl . '?p=' . $page;
            $this->info("\n--- Page {$page} : {$url} ---");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->get($url);

            if ($response->failed()) {
                $this->warn(" -> Erreur HTTP (Code : " . $response->status() . ").");
                break;
            }

            $crawler = new Crawler($response->body());
            $nodes = $crawler->filter('.product-item, li.item.product, [data-product-id]');
            $this->info(" -> Produits détectés dans le HTML : " . $nodes->count());

            if ($nodes->count() === 0) {
                $this->line(" -> Plus de produits détectés, fin de la pagination.");
                break;
            }

            foreach ($nodes as $i => $domElement) {
                try {
                    $node = new Crawler($domElement);

                    // Extraction du nom et du lien de la page produit
                    $name = '';
                    $productUrl = '';

                    $linkNode = $node->filter('a.product-item-photo, a.product.photo, a[href*=".html"]');
                    if ($linkNode->count() > 0) {
                        $productUrl = $linkNode->attr('href');
                    }

                    foreach (['h1', 'h2', 'h3', 'strong', 'a'] as $tag) {
                        $filtered = $node->filter($tag);
                        if ($filtered->count() > 0) {
                            foreach ($filtered as $el) {
                                $txt = trim($el->textContent);
                                if (strlen($txt) > 10 && !str_contains(strtolower($txt), 'ajouter') && !str_contains(strtolower($txt), 'stock')) {
                                    $name = $txt;
                                    break 2;
                                }
                            }
                        }
                    }

                    if (empty($name)) {
                        continue;
                    }

                    // Vérification en base de données pour éviter les doublons
                    $product = Product::where('sous_categorie_id', $sousCategorie->id)
                                      ->where('name', $name)
                                      ->first();

                    if ($product && !empty($product->photo)) {
                        $totalProcessed++;
                        continue;
                    }

                    // Extraction de l'image via la balise og:image de la fiche produit
                    $imageUrl = null;
                    if (!empty($productUrl)) {
                        try {
                            $productPageResponse = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                                'Referer' => $baseUrl
                            ])->timeout(8)->get($productUrl);

                            if ($productPageResponse->successful()) {
                                $productCrawler = new Crawler($productPageResponse->body());
                                $ogImageNode = $productCrawler->filter('meta[property="og:image"]');
                                if ($ogImageNode->count() > 0) {
                                    $imageUrl = trim($ogImageNode->attr('content'));
                                }
                            }
                        } catch (\Exception $e) {
                            // Gestion silencieuse des erreurs réseau individuelles
                        }
                    }

                    $localImagePath = null;
                    if ($imageUrl) {
                        if (str_starts_with($imageUrl, '//')) {
                            $imageUrl = 'https:' . $imageUrl;
                        }

                        try {
                            $responseImg = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                                'Referer' => $productUrl
                            ])->timeout(10)->get($imageUrl);

                            if ($responseImg->successful() && !empty($responseImg->body())) {
                                $imageName = Str::slug(substr($name, 0, 40)) . '-' . time() . '-' . rand(100, 999) . '.jpg';
                                Storage::disk('public')->put('products/' . $imageName, $responseImg->body());
                                $localImagePath = 'storage/products/' . $imageName;
                            }
                        } catch (\Exception $ex) {
                            // Ignorer l'échec de téléchargement individuel
                        }
                    }

                    // Extraction du prix
                    $priceText = '';
                    $priceElements = $node->filter('.price, span[data-price-amount], .price-wrapper, .normal-price .price');
                    if ($priceElements->count() > 0) {
                        $priceText = $priceElements->text();
                    } else {
                        $priceText = $node->text();
                    }

                    preg_match('/([\d\s,\.]+)\s*€/', $priceText, $matches);
                    $price = null;
                    if (isset($matches[1])) {
                        $cleanPrice = str_replace([' ', ','], ['', '.'], trim($matches[1]));
                        $price = is_numeric($cleanPrice) ? (float)$cleanPrice : null;
                    }

                    // Enregistrement ou mise à jour sécurisée en base de données
                    if ($product) {
                        $product->update([
                            'price' => $price ?? $product->price,
                            'photo' => $localImagePath ?? $product->photo,
                        ]);
                        $totalUpdated++;
                        $this->line("    [🔄] Mis à jour : " . substr($name, 0, 40) . "... " . ($localImagePath ? "[Photo Ajoutée]" : "[Sans Photo]"));
                    } else {
                        Product::create([
                            'sous_categorie_id' => $sousCategorie->id,
                            'name' => $name,
                            'price' => $price,
                            'photo' => $localImagePath,
                            'description' => "Collier et accessoire de fixation de qualité professionnelle disponible sur 123elec.",
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $totalUpdated++;
                        $this->line("    [✔] Inséré : " . substr($name, 0, 40) . "... " . ($localImagePath ? "[Avec Photo]" : "[Sans Photo]"));
                    }

                    $totalProcessed++;
                } catch (\Exception $e) {
                    $this->error("    [X] Erreur : " . $e->getMessage());
                }
            }

            $nextPage = $crawler->filter('.pages-item-next a, a.action.next, [rel="next"]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Total traité : {$totalProcessed} | Total mis à jour/insérés : {$totalUpdated}");
    }
}
