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

class ScrapeDataCommGlobal extends Command
{
    protected $signature = 'datacomm:global-scrape';
    protected $description = 'Scrape toutes les catégories et produits de Data Comm Express';

    public function handle()
    {
        $this->info('Début du scraping global de Data Comm Express...');

        $baseUrl = 'https://datacommexpress.com/';
        
        try {
            $response = Http::timeout(60)->retry(3, 1000)->get($baseUrl);
            if ($response->failed()) {
                $this->error("Impossible d'accéder à la page d'accueil de Data Comm Express.");
                return;
            }

            $crawler = new Crawler($response->body());

            // Ciblage précis du menu latéral visible sur la capture (les départements / catégories)
            $categoryLinks = [];
            
            // On cible les liens situés dans la zone des départements / menus verticaux
            $crawler->filter('.departements a, .elementor-icon-list-items a, ul.product-categories li a, .menu-item a, .vertical-menu a, .et_pb_menu_page_url a')->each(function (Crawler $node) use (&$categoryLinks) {
                $href = $node->attr('href');
                $name = trim($node->text());
                
                // Nettoyer le nom pour enlever les éventuels retours à la ligne
                $name = preg_replace('/\s+/', ' ', $name);

                if ($href && $name && !in_array($href, array_keys($categoryLinks))) {
                    // On filtre pour ne garder que les liens de catégories de produits ou de marques
                    if (str_contains($href, 'product-category') || str_contains($href, 'brand') || str_contains($href, 'shop')) {
                        $categoryLinks[$href] = $name;
                    }
                }
            });

            // Si le premier filtre est trop strict, on ratisse plus large sur tous les liens du menu de gauche
            if (count($categoryLinks) === 0) {
                $crawler->filter('aside a, .sidebar a, nav a')->each(function (Crawler $node) use (&$categoryLinks) {
                    $href = $node->attr('href');
                    $name = trim($node->text());
                    if ($href && $name && str_contains($href, 'datacommexpress.com') && !in_array($href, array_keys($categoryLinks))) {
                        if (str_contains($href, 'product-category') || str_contains($href, 'brand')) {
                            $categoryLinks[$href] = $name;
                        }
                    }
                });
            }

            if (empty($categoryLinks)) {
                $this->warn("Aucune catégorie spécifique détectée par les filtres.");
                return;
            }

            $this->info("=> " . count($categoryLinks) . " catégories/départements détectés avec succès !");

            foreach ($categoryLinks as $catUrl => $catName) {
                $this->info("--------------------------------------------------");
                $this->info("Exploration de la catégorie : {$catName} ({$catUrl})");

                $category = Category::firstOrCreate(['nom' => $catName]);
                
                $page = 1;
                $hasNextPage = true;

                while ($hasNextPage && $page <= 10) {
                    $paginatedUrl = $page === 1 ? $catUrl : rtrim($catUrl, '/') . '/page/' . $page . '/';
                    
                    try {
                        $catResponse = Http::timeout(60)->retry(3, 1000)->get($paginatedUrl);
                        if ($catResponse->failed()) {
                            break;
                        }

                        $catCrawler = new Crawler($catResponse->body());
                        $productNodes = $catCrawler->filter('ul.products li.product, .product-item, .type-product');

                        if ($productNodes->count() === 0) {
                            break;
                        }

                        $productNodes->each(function (Crawler $node) use ($category) {
                            try {
                                $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                                if ($titleNode->count() === 0) return;
                                $productName = trim($titleNode->text());

                                $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                                $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                                $imgNode = $node->filter('img');
                                $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                                $description = "Produit importé depuis Data Comm Express";
                                $photoPath = null;
                                $sousCatName = "Général";

                                if ($productUrl) {
                                    try {
                                        $prodResponse = Http::timeout(15)->get($productUrl);
                                        if ($prodResponse->successful()) {
                                            $prodCrawler = new Crawler($prodResponse->body());
                                            
                                            $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                                            if ($descNode->count() > 0) {
                                                $description = trim($descNode->text());
                                            }

                                            $breadcrumbNode = $prodCrawler->filter('.woocommerce-breadcrumb, nav.woocommerce-breadcrumb, .pathway');
                                            if ($breadcrumbNode->count() > 0) {
                                                $crumbs = $breadcrumbNode->filter('a');
                                                if ($crumbs->count() >= 2) {
                                                    $sousCatName = trim($crumbs->last()->text());
                                                }
                                            }
                                        }
                                    } catch (\Exception $ex) {
                                        // Ignore
                                    }
                                }

                                $sousCat = SousCategorie::firstOrCreate([
                                    'category_id' => $category->id,
                                    'nom' => $sousCatName
                                ]);

                                if ($imgUrl) {
                                    try {
                                        $imageContents = @file_get_contents($imgUrl);
                                        if ($imageContents) {
                                            $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                                            Storage::disk('public')->put($imageName, $imageContents);
                                            $photoPath = $imageName;
                                        }
                                    } catch (\Exception $ex) {
                                        // Ignore
                                    }
                                }

                                Product::firstOrCreate(
                                    [
                                        'name' => $productName,
                                        'sous_categorie_id' => $sousCat->id
                                    ],
                                    [
                                        'description' => $description,
                                        'photo' => $photoPath,
                                        'price' => null, // Prix forcé à null
                                        'stock' => true
                                    ]
                                );

                                $this->line("   -> [AJOUTÉ] {$productName}");

                            } catch (\Exception $e) {
                                // Ignore
                            }
                        });

                        $nextPageNode = $catCrawler->filter('a.next.page-numbers');
                        if ($nextPageNode->count() > 0) {
                            $page++;
                            sleep(2);
                        } else {
                            $hasNextPage = false;
                        }

                    } catch (\Exception $e) {
                        break;
                    }
                }
            }

            $this->info('Scraping global de Data Comm Express terminé avec succès !');

        } catch (\Exception $e) {
            $this->error("Erreur générale : " . $e->getMessage());
        }
    }
}