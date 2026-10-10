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

class ScrapeTogoInformatique extends Command
{
    protected $signature = 'togoinformatique:scrape';
    protected $description = 'Scrape tous les produits de Togo Informatique avec gestion robuste des timeouts';

    public function handle()
    {
        $this->info('Début du scraping global de Togo Informatique...');

        $baseUrl = 'https://www.togoinformatique.com/product-category/ordinateurs/ordinateur-portable/';
        $page = 1;
        $hasNextPage = true;

        $category = Category::firstOrCreate(['nom' => 'Ordinateurs & Portables']);

        while ($hasNextPage && $page <= 20) { // Sécurité pour éviter une boucle infinie
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
            $this->info("--------------------------------------------------");
            $this->info("Scraping de la page {$page} : {$url}");

            try {
                // Timeout élargi à 60 secondes pour éviter les erreurs cURL 28
                $response = Http::timeout(60)->retry(3, 1000)->get($url);

                if ($response->failed()) {
                    $this->warn("Impossible d'accéder à la page {$page} (Code: {$response->status()}). Fin du scraping.");
                    break;
                }

                $crawler = new Crawler($response->body());
                $productNodes = $crawler->filter('ul.products li.product, .product-item, .type-product');
                
                if ($productNodes->count() === 0) {
                    $this->info("Aucun produit trouvé sur cette page. Fin du scraping.");
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

                        $description = "Produit importé depuis Togo Informatique";
                        $photoPath = null;
                        $sousCatName = "Ordinateurs Portables";

                        // Récupération de la fiche produit avec un timeout court et gestion d'erreur silencieuse
                        if ($productUrl) {
                            try {
                                $prodResponse = Http::timeout(15)->get($productUrl);
                                if ($prodResponse->successful()) {
                                    $prodCrawler = new Crawler($prodResponse->body());
                                    
                                    $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                                    if ($descNode->count() > 0) {
                                        $description = trim($descNode->text());
                                    }

                                    $breadcrumbNode = $prodCrawler->filter('.woocommerce-breadcrumb, nav.woocommerce-breadcrumb');
                                    if ($breadcrumbNode->count() === 0) {
                                        // Essayer un autre sélecteur de fil d'ariane si le premier échoue
                                        $breadcrumbNode = $prodCrawler->filter('.pathway, .breadcrumbs');
                                    }

                                    if ($breadcrumbNode->count() > 0) {
                                        $crumbs = $breadcrumbNode->filter('a');
                                        if ($crumbs->count() >= 2) {
                                            $sousCatName = trim($crumbs->last()->text());
                                        }
                                    }
                                }
                            } catch (\Exception $ex) {
                                // Ignore les erreurs de sous-page individuelle pour ne pas bloquer le script
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
                                // Ignore les erreurs d'image
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
                                'price' => null, // Pas de faux prix
                                'stock' => true
                            ]
                        );

                        $this->line("   -> [AJOUTÉ] {$productName}");

                    } catch (\Exception $e) {
                        // Ignore un produit défectueux pour continuer la boucle
                    }
                });

                // Vérifier s'il y a une page suivante
                $nextPageNode = $crawler->filter('a.next.page-numbers');
                if ($nextPageNode->count() > 0) {
                    $page++;
                    sleep(2); // Petite pause de 2 secondes pour soulager le serveur distant
                } else {
                    $hasNextPage = false;
                }

            } catch (\Exception $e) {
                $this->error("Erreur de connexion sur la page {$page} : " . $e->getMessage());
                $this->warn("Attente de 5 secondes avant de réessayer...");
                sleep(5);
                // On retente la même page ou on passe à la suivante selon ton choix, ici on retente en incrémentant ou en rebouclant
                $page++; 
            }
        }

        $this->info('Scraping terminé avec succès !');
    }
}