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

class ScrapeMoteursElectriques extends Command
{
    protected $signature = 'moteurs:scrape';
    protected $description = 'Scrape les produits de la catégorie Électricité (Moteurs Électriques) avec photos et prix à NULL';

    public function handle()
    {
        $this->info('Début du scraping des moteurs électriques...');

        // URL de base de la catégorie cible
        $baseUrl = 'https://www.google.com/url?q=https://www.togoinformatique.com/'; // Adaptez l'URL de base si nécessaire selon le site source réel, ou utilisez l'URL directe du catalogue d'électricité

        // Note : Si le lien court redirige vers une page spécifique de produits électriques, mettez l'URL finale directement ici :
        $currentUrl = 'https://www.togoinformatique.com/product-category/electricite/'; // Remplacez par l'URL exacte de la catégorie électricité du site cible

        $page = 1;
        $hasNextPage = true;

        while ($hasNextPage && $page <= 10) {
            $url = $page === 1 ? $currentUrl : $currentUrl . 'page/' . $page . '/';
            $this->info("--------------------------------------------------");
            $this->info("Scraping de la page {$page} : {$url}");

            try {
                $response = Http::timeout(60)->retry(3, 1000)->get($url);

                if ($response->failed()) {
                    $this->warn("Impossible d'accéder à la page {$page} (Code: {$response->status()}). Fin du scraping.");
                    break;
                }

                $crawler = new Crawler($response->body());
                // Sélecteurs standards WooCommerce / e-commerce
                $productNodes = $crawler->filter('ul.products li.product, .product-item, .type-product');

                if ($productNodes->count() === 0) {
                    $this->info("Aucun produit trouvé sur cette page. Fin du scraping.");
                    break;
                }

                $productNodes->each(function (Crawler $node) {
                    try {
                        // Récupération du nom du produit
                        $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                        if ($titleNode->count() === 0) return;
                        $productName = trim($titleNode->text());

                        // Récupération du lien de la fiche produit
                        $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                        $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                        // Récupération de l'image
                        $imgNode = $node->filter('img');
                        $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                        // RÈGLE STRICTE : AUCUN PRODUIT SANS PHOTO
                        if (!$imgUrl) {
                            $this->line("    [IGNORÉ - Pas d'URL image] {$productName}");
                            return;
                        }

                        $description = "Moteur électrique et équipement importé";

                        // Visite de la page de détail pour enrichir la description si disponible
                        if ($productUrl) {
                            try {
                                $prodResponse = Http::timeout(15)->get($productUrl);
                                if ($prodResponse->successful()) {
                                    $prodCrawler = new Crawler($prodResponse->body());

                                    $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                                    if ($descNode->count() > 0) {
                                        $description = trim($descNode->text());
                                    }
                                }
                            } catch (\Exception $ex) {}
                        }

                        // Téléchargement et stockage local de l'image
                        $photoPath = null;
                        try {
                            $imageContents = @file_get_contents($imgUrl);
                            if ($imageContents) {
                                $imageName = 'products/moteurs-' . Str::slug($productName) . '-' . time() . '.jpg';
                                Storage::disk('public')->put($imageName, $imageContents);
                                $photoPath = $imageName;
                            }
                        } catch (\Exception $ex) {}

                        if (!$photoPath) {
                            $this->line("    [IGNORÉ - Échec téléchargement image] {$productName}");
                            return;
                        }

                        // Attribution forcée aux catégories demandées
                        $mainCategoryName = 'Électricité';
                        $sousCatName = 'Moteur Électrique';

                        $category = Category::firstOrCreate(['nom' => $mainCategoryName]);

                        $sousCat = SousCategorie::firstOrCreate([
                            'category_id' => $category->id,
                            'nom' => $sousCatName
                        ]);

                        // Enregistrement du produit avec un prix STRICTEMENT NULL
                        Product::firstOrCreate(
                            [
                                'name' => $productName,
                                'sous_categorie_id' => $sousCat->id
                            ],
                            [
                                'description' => $description,
                                'photo' => $photoPath,
                                'price' => null, // Strictement NULL
                                'stock' => true
                            ]
                        );

                        $this->line("    [SUCCÈS] {$productName} -> [{$category->nom} > {$sousCat->nom}]");

                    } catch (\Exception $e) {}
                });

                // Gestion de la pagination suivante
                $nextPageNode = $crawler->filter('a.next.page-numbers');
                if ($nextPageNode->count() > 0) {
                    $page++;
                    sleep(2);
                } else {
                    $hasNextPage = false;
                }

            } catch (\Exception $e) {
                $this->error("Erreur de connexion sur la page {$page} : " . $e->getMessage());
                sleep(5);
                $page++;
            }
        }

        $this->info('Scraping des moteurs électriques terminé avec succès !');
    }
}
