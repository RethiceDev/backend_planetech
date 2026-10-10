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

class ScrapeProtechGlobal extends Command
{
    protected $signature = 'protech:global-scrape';
    protected $description = 'Scrape toutes les catégories et produits de Protech Togo de fond en comble';

    public function handle()
    {
        $this->info('Début du scraping global de Protech Togo...');

        $baseUrl = 'https://protechtogo.com/boutique/';
        
        try {
            $response = Http::timeout(60)->retry(3, 1000)->get($baseUrl);
            if ($response->failed()) {
                $this->error("Impossible d'accéder à la boutique Protech Togo.");
                return;
            }

            $crawler = new Crawler($response->body());

            // Extraction des catégories/sous-catégories depuis le menu de la boutique
            $categoryLinks = [];
            $crawler->filter('.product-categories a, .widget_product_categories a, .woo-categories a, ul.product-categories li a')->each(function (Crawler $node) use (&$categoryLinks) {
                $href = $node->attr('href');
                $name = trim($node->text());
                
                // Nettoyage du nom pour enlever les compteurs entre parenthèses (ex: "(15)")
                $name = preg_replace('/\s*\(\d+\)\s*$/', '', $name);
                $name = preg_replace('/\s+/', ' ', $name);

                if ($href && $name && !in_array($href, array_keys($categoryLinks))) {
                    if (str_contains($href, 'product-category')) {
                        $categoryLinks[$href] = $name;
                    }
                }
            });

            // Si aucune catégorie spécifique n'est trouvée, on prend l'URL de la boutique principale
            if (empty($categoryLinks)) {
                $this->warn("Aucune catégorie détectée par le filtre, utilisation de la boutique globale.");
                $categoryLinks[$baseUrl] = "Catalogue Protech";
            }

            $this->info("=> " . count($categoryLinks) . " catégories/sous-catégories détectées !");

            foreach ($categoryLinks as $catUrl => $catName) {
                $this->info("--------------------------------------------------");
                $this->info("Exploration de la catégorie : {$catName} ({$catUrl})");

                $category = Category::firstOrCreate(['nom' => $catName]);
                
                $page = 1;
                $hasNextPage = true;

                while ($hasNextPage && $page <= 25) {
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

                                $description = "Produit importé depuis Protech Togo";
                                $photoPath = null;
                                $sousCatName = $catName;

                                if ($productUrl) {
                                    try {
                                        $prodResponse = Http::timeout(15)->get($productUrl);
                                        if ($prodResponse->successful()) {
                                            $prodCrawler = new Crawler($prodResponse->body());
                                            
                                            $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                                            if ($descNode->count() > 0) {
                                                $description = trim($descNode->text());
                                            }

                                            // Récupération de la sous-catégorie via le fil d'Ariane si présent
                                            $breadcrumbNode = $prodCrawler->filter('.woocommerce-breadcrumb, nav.woocommerce-breadcrumb');
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

                                $this->line("      [AJOUTÉ] {$productName}");

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

            $this->info('Scraping global de Protech Togo terminé avec succès !');

        } catch (\Exception $e) {
            $this->error("Erreur générale : " . $e->getMessage());
        }
    }
}