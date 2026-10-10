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

class ScrapeETechnologies extends Command
{
    protected $signature = 'etechnologies:scrape';
    protected $description = 'Scrape toutes les catégories, sous-catégories et produits de E-technologies de fond en comble';

    public function handle()
    {
        $this->info('Début du scraping global de E-technologies...');

        // URL de la boutique ou d'une catégorie principale pour démarrer
        $baseUrl = 'https://etechnologies.shop/shop/';
        
        try {
            $response = Http::timeout(60)->retry(3, 1000)->get($baseUrl);
            if ($response->failed()) {
                $this->error("Impossible d'accéder à la boutique E-technologies.");
                return;
            }

            $crawler = new Crawler($response->body());

            // 1. Récupérer toutes les catégories du menu de la boutique/sidebar
            $categoryLinks = [];
            $crawler->filter('.product-categories a, .widget_product_categories a, .woo-categories a, nav.woocommerce-breadcrumb a, .menu-item a')->each(function (Crawler $node) use (&$categoryLinks) {
                $href = $node->attr('href');
                $name = trim($node->text());
                
                // Nettoyage du nom (retirer les compteurs de produits entre parenthèses si présents, ex: "Câblage (12)")
                $name = preg_replace('/\s*\(\d+\)\s*$/', '', $name);

                if ($href && $name && !in_array($href, array_keys($categoryLinks))) {
                    if (str_contains($href, 'product-category')) {
                        $categoryLinks[$href] = $name;
                    }
                }
            });

            // Si aucune catégorie spécifique n'est trouvée, on prend l'URL de base de la boutique
            if (empty($categoryLinks)) {
                $this->warn("Aucune catégorie détectée par le filtre, utilisation de la boutique globale.");
                $categoryLinks[$baseUrl] = "Catalogue E-technologies";
            }

            $this->info("=> " . count($categoryLinks) . " catégories/sous-catégories détectées !");

            // 2. Parcourir chaque catégorie
            foreach ($categoryLinks as $catUrl => $catName) {
                $this->info("--------------------------------------------------");
                $this->info("Exploration de la catégorie : {$catName} ({$catUrl})");

                $category = Category::firstOrCreate(['nom' => $catName]);
                
                $page = 1;
                $hasNextPage = true;

                // 3. Boucle de pagination pour chaque catégorie (jusqu'à 20 pages de sécurité)
                while ($hasNextPage && $page <= 20) {
                    // Gestion propre de l'URL paginée WooCommerce (ex: /page/2/)
                    $paginatedUrl = $page === 1 ? $catUrl : rtrim($catUrl, '/') . '/page/' . $page . '/';
                    
                    try {
                        $catResponse = Http::timeout(60)->retry(3, 1000)->get($paginatedUrl);
                        if ($catResponse->failed()) {
                            break;
                        }

                        $catCrawler = new Crawler($catResponse->body());
                        $productNodes = $catCrawler->filter('ul.products li.product, .product-item, .type-product');

                        if ($productNodes->count() === 0) {
                            break; // Plus de produits sur cette page, on sort de la pagination
                        }

                        $this->info("   -> Scraping de la page {$page} (" . $productNodes->count() . " produits trouvés)...");

                        $productNodes->each(function (Crawler $node) use ($category, $catName) {
                            try {
                                $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                                if ($titleNode->count() === 0) return;
                                $productName = trim($titleNode->text());

                                $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                                $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                                $imgNode = $node->filter('img');
                                $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                                $description = "Produit importé depuis E-technologies";
                                $photoPath = null;
                                $sousCatName = $catName; // Par défaut, prend le nom de la catégorie parente

                                // Récupérer les détails sur la fiche produit individuelle
                                if ($productUrl) {
                                    try {
                                        $prodResponse = Http::timeout(15)->get($productUrl);
                                        if ($prodResponse->successful()) {
                                            $prodCrawler = new Crawler($prodResponse->body());
                                            
                                            $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                                            if ($descNode->count() > 0) {
                                                $description = trim($descNode->text());
                                            }

                                            // Affiner la sous-catégorie via le fil d'Ariane si disponible
                                            $breadcrumbNode = $prodCrawler->filter('.woocommerce-breadcrumb, nav.woocommerce-breadcrumb');
                                            if ($breadcrumbNode->count() > 0) {
                                                $crumbs = $breadcrumbNode->filter('a');
                                                if ($crumbs->count() >= 2) {
                                                    $sousCatName = trim($crumbs->last()->text());
                                                }
                                            }
                                        }
                                    } catch (\Exception $ex) {
                                        // Ignore l'erreur de fiche individuelle
                                    }
                                }

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
                                    } catch (\Exception $ex) {
                                        // Ignore l'erreur d'image
                                    }
                                }

                                // Enregistrement en base de données (Prix forcé à NULL)
                                Product::firstOrCreate(
                                    [
                                        'name' => $productName,
                                        'sous_categorie_id' => $sousCat->id
                                    ],
                                    [
                                        'description' => $description,
                                        'photo' => $photoPath,
                                        'price' => null, // Pas de faux prix extrait
                                        'stock' => true
                                    ]
                                );

                                $this->line("      [AJOUTÉ] {$productName}");

                            } catch (\Exception $e) {
                                // Ignore un produit individuel en erreur
                            }
                        });

                        // Vérifier s'il existe une page suivante dans la pagination WooCommerce
                        $nextPageNode = $catCrawler->filter('a.next.page-numbers');
                        if ($nextPageNode->count() > 0) {
                            $page++;
                            sleep(2); // Petite pause pour respecter le serveur distant
                        } else {
                            $hasNextPage = false;
                        }

                    } catch (\Exception $e) {
                        break;
                    }
                }
            }

            $this->info('Scraping global de E-technologies terminé avec succès !');

        } catch (\Exception $e) {
            $this->error("Erreur générale : " . $e->getMessage());
        }
    }
}