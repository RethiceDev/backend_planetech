<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Category;
use App\Models\SousCategorie;
use App\Models\Product;

class ScrapeMaterielElectriqueCoffret extends Command
{
    protected $signature = 'materielelectrique:scrape-coffrets';
    protected $description = 'Scrape les coffrets depuis materielelectrique.com';

    public function handle()
    {
        $this->info('Début du scraping des coffrets sur Matériel Électrique...');

        $baseUrl = 'https://www.materielelectrique.com/c/coffret-1-100072';
        
        // Création de la catégorie principale
        $category = Category::firstOrCreate(['nom' => 'Matériel Électrique']);
        $sousCat = SousCategorie::firstOrCreate([
            'category_id' => $category->id,
            'nom' => 'Coffrets Électriques'
        ]);

        $page = 1;
        $hasNextPage = true;

        while ($hasNextPage && $page <= 30) { // Sécurité pour parcourir les pages multiples
            // Pagination typique de ce type de catalogue (?p=2, ?p=3, etc.)
            $url = $page === 1 ? $baseUrl : $baseUrl . '?p=' . $page;

            $this->info("--------------------------------------------------");
            $this->info("Scraping de la page {$page} : {$url}");

            try {
                // Utilisation d'un User-Agent complet pour simuler un vrai navigateur
                $response = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                    ->withHeaders([
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                    ])
                    ->timeout(45)
                    ->retry(3, 2000)
                    ->get($url);

                if ($response->failed()) {
                    $this->warn("Impossible d'accéder à la page {$page}. Fin du scraping.");
                    break;
                }

                $crawler = new Crawler($response->body());
                
                // Sélecteurs de produits adaptés aux grilles de listes de produits
                $productNodes = $crawler->filter('.product-item, .product-miniature, .item-product, .product-card, .list-product li');

                if ($productNodes->count() === 0) {
                    $this->info("Aucun produit trouvé sur la page {$page}. Fin du scraping.");
                    break;
                }

                $this->info("-> {$productNodes->count()} produits trouvés. Traitement en cours...");

                $productNodes->each(function (Crawler $node) use ($sousCat) {
                    try {
                        // Nom du produit
                        $titleNode = $node->filter('.product-title, .product-name, h2 a, h3 a, a.product-link');
                        if ($titleNode->count() === 0) return;
                        $productName = trim($titleNode->text());

                        // Lien vers la fiche produit
                        $linkNode = $node->filter('a');
                        $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;
                        
                        // S'assurer que l'URL est absolue
                        if ($productUrl && !str_starts_with($productUrl, 'http')) {
                            $productUrl = 'https://www.materielelectrique.com' . $productUrl;
                        }

                        // Prix
                        $priceNode = $node->filter('.price, .product-price, span.amount');
                        $price = null;
                        if ($priceNode->count() > 0) {
                            $priceText = preg_replace('/[^0-9.,]/', '', $priceNode->text());
                            $price = $priceText ? floatval(str_replace(',', '.', $priceText)) : null;
                        }

                        // Image
                        $imgNode = $node->filter('img');
                        $imgUrl = null;
                        if ($imgNode->count() > 0) {
                            $imgUrl = $imgNode->attr('data-src') 
                                ?: ($imgNode->attr('data-lazy-src') ?: $imgNode->attr('src'));
                        }

                        $description = "Coffret électrique importé depuis materielelectrique.com";
                        $photoPath = null;

                        // Récupération de la description détaillée sur la fiche produit
                        if ($productUrl) {
                            try {
                                $prodResponse = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
                                    ->timeout(10)
                                    ->get($productUrl);

                                if ($prodResponse->successful()) {
                                    $prodCrawler = new Crawler($prodResponse->body());
                                    $descNode = $prodCrawler->filter('.product-description, #description, .description-text, .rte');
                                    if ($descNode->count() > 0) {
                                        $description = trim($descNode->text());
                                    }
                                }
                            } catch (\Exception $e) {}
                        }

                        // Téléchargement de l'image
                        if ($imgUrl) {
                            try {
                                $imageContents = @file_get_contents($imgUrl);
                                if ($imageContents) {
                                    $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                                    Storage::disk('public')->put($imageName, $imageContents);
                                    $photoPath = $imageName;
                                }
                            } catch (\Exception $e) {}
                        }

                        // Enregistrement en base de données
                        Product::firstOrCreate(
                            [
                                'name' => $productName,
                                'sous_categorie_id' => $sousCat->id
                            ],
                            [
                                'description' => $description,
                                'photo' => $photoPath,
                                'price' => $price,
                                'stock' => true
                            ]
                        );

                        $this->info("   -> [AJOUTÉ] {$productName} | Prix: " . ($price ?? 'N/C'));

                    } catch (\Exception $e) {}
                });

                $page++;
                usleep(500000); // Pause de 0.5s

            } catch (\Exception $e) {
                $this->error("Erreur réseau sur la page {$page} : " . $e->getMessage());
                break;
            }
        }

        $this->info('Scraping des coffrets Matériel Électrique terminé avec succès !');
    }
}