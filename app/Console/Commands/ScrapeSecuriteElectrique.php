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

class ScrapeSecuriteElectrique extends Command
{
    protected $signature = 'protechtogo:scrape-securite';
    protected $description = 'Scrape tous les produits de la catégorie Sécurité Électrique sur protechtogo.com';

    public function handle()
    {
        $this->info('Début du scraping de la catégorie Sécurité Électrique...');

        $baseUrl = 'https://protechtogo.com/product-category/securite-electrique/';
        
        // Création de la catégorie principale
        $category = Category::firstOrCreate(['nom' => 'Sécurité & Électricité']);

        $page = 1;
        $hasNextPage = true;

        while ($hasNextPage && $page <= 20) { // Sécurité pour éviter une boucle infinie
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';

            $this->info("--------------------------------------------------");
            $this->info("Scraping de la page {$page} : {$url}");

            try {
                $response = Http::timeout(45)->retry(3, 2000)->get($url);

                if ($response->failed()) {
                    $this->warn("Impossible d'accéder à la page {$page}. Fin du scraping.");
                    break;
                }

                $crawler = new Crawler($response->body());
                $productNodes = $crawler->filter('ul.products li.product, .product-item, .type-product');

                if ($productNodes->count() === 0) {
                    $this->info("Aucun produit trouvé sur la page {$page}. Fin du scraping.");
                    break;
                }

                $this->info("-> {$productNodes->count()} produits trouvés. Traitement en cours...");

                $productNodes->each(function (Crawler $node) use ($category) {
                    try {
                        // Nom du produit
                        $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                        if ($titleNode->count() === 0) return;
                        $productName = trim($titleNode->text());

                        // Lien vers la fiche produit
                        $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                        $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                        // Prix
                        $priceNode = $node->filter('.price, .amount');
                        $price = null;
                        if ($priceNode->count() > 0) {
                            $priceText = preg_replace('/[^0-9.,]/', '', $priceNode->text());
                            $price = $priceText ? floatval(str_replace(',', '.', $priceText)) : null;
                        }

                        // Image
                        $imgNode = $node->filter('img');
                        $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                        $description = "Produit importé depuis Protech Togo";
                        $photoPath = null;
                        $sousCatName = "Sécurité Électrique";

                        // Récupération des détails sur la fiche du produit
                        if ($productUrl) {
                            try {
                                $prodResponse = Http::timeout(10)->get($productUrl);
                                if ($prodResponse->successful()) {
                                    $prodCrawler = new Crawler($prodResponse->body());
                                    
                                    // Description
                                    $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                                    if ($descNode->count() > 0) {
                                        $description = trim($descNode->text());
                                    }

                                    // Sous-catégorie via le fil d'ariane
                                    $breadcrumbNode = $prodCrawler->filter('.woocommerce-breadcrumb, nav.woocommerce-breadcrumb');
                                    if ($breadcrumbNode->count() > 0) {
                                        $crumbs = $breadcrumbNode->filter('a');
                                        if ($crumbs->count() >= 2) {
                                            $sousCatName = trim($crumbs->last()->text());
                                        }
                                    }
                                }
                            } catch (\Exception $e) {}
                        }

                        // Gestion de la sous-catégorie
                        $sousCat = SousCategorie::firstOrCreate([
                            'category_id' => $category->id,
                            'nom' => $sousCatName
                        ]);

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

                // Incrémentation de la page
                $page++;
                usleep(500000); // Pause de 0.5s

            } catch (\Exception $e) {
                $this->error("Erreur réseau sur la page {$page} : " . $e->getMessage());
                break;
            }
        }

        $this->info('Scraping de la catégorie Sécurité Électrique terminé avec succès !');
    }
}