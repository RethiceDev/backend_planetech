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

class ScrapeFibresOptiques extends Command
{
    protected $signature = 'etechnologies:fibres-optiques';
    protected $description = 'Scrape tous les produits de la catégorie Fibres Optiques sur E-technologies avec pagination complète';

    public function handle()
    {
        $this->info('Début du scraping de la catégorie Fibres Optiques (E-technologies)...');

        $baseUrl = 'https://etechnologies.shop/product-category/cablage/fibres-optiques/';
        
        // Créer ou récupérer la catégorie parente et la sous-catégorie
        $category = Category::firstOrCreate(['nom' => 'Câblage']);
        $sousCat = SousCategorie::firstOrCreate([
            'category_id' => $category->id,
            'nom' => 'Fibres Optiques'
        ]);

        $page = 1;
        $hasNextPage = true;

        while ($hasNextPage && $page <= 30) { // Sécurité fixée à 30 pages max
            // Gestion de l'URL paginée (WooCommerce standard : /page/X/)
            $url = $page === 1 ? $baseUrl : rtrim($baseUrl, '/') . '/page/' . $page . '/';
            
            $this->info("--------------------------------------------------");
            $this->info("Scraping de la page {$page} : {$url}");

            try {
                $response = Http::timeout(60)->retry(3, 1000)->get($url);

                if ($response->failed()) {
                    $this->warn("Fin de la pagination ou page introuvable (Code: {$response->status()}).");
                    break;
                }

                $crawler = new Crawler($response->body());
                $productNodes = $crawler->filter('ul.products li.product, .product-item, .type-product');

                if ($productNodes->count() === 0) {
                    $this->info("Aucun produit trouvé sur cette page. Fin du scraping.");
                    break;
                }

                $this->info("   -> " . $productNodes->count() . " produits détectés sur cette page.");

                $productNodes->each(function (Crawler $node) use ($sousCat) {
                    try {
                        // Nom du produit
                        $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                        if ($titleNode->count() === 0) return;
                        $productName = trim($titleNode->text());

                        // Lien vers la fiche produit
                        $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                        $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                        // Image
                        $imgNode = $node->filter('img');
                        $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                        $description = "Produit importé depuis E-technologies (Fibres Optiques)";
                        $photoPath = null;

                        // Récupération de la description détaillée sur la page produit
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
                            } catch (\Exception $ex) {
                                // Ignore l'erreur de sous-page pour ne pas bloquer
                            }
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
                                'price' => null, // Aucun faux prix extrait
                                'stock' => true
                            ]
                        );

                        $this->line("      [AJOUTÉ] {$productName}");

                    } catch (\Exception $e) {
                        // Ignore un produit isolé en erreur
                    }
                });

                // Vérifier s'il y a une page suivante (bouton .next.page-numbers ou lien suivant)
                $nextPageNode = $crawler->filter('a.next.page-numbers, .pagination .next');
                if ($nextPageNode->count() > 0) {
                    $page++;
                    sleep(2); // Pause de 2 secondes pour ne pas surcharger le serveur
                } else {
                    $hasNextPage = false;
                }

            } catch (\Exception $e) {
                $this->error("Erreur sur la page {$page} : " . $e->getMessage());
                break;
            }
        }

        $this->info('Scraping de la catégorie Fibres Optiques terminé avec succès !');
    }
}