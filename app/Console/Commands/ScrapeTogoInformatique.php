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
    protected $description = 'Scrape et range intelligemment les produits de Togo Informatique avec photos et prix à NULL';

    public function handle()
    {
        $this->info('Début du scraping global de Togo Informatique...');

        $baseUrl = 'https://www.togoinformatique.com/product-category/ordinateurs/ordinateur-portable/';
        $page = 1;
        $hasNextPage = true;

        while ($hasNextPage && $page <= 20) {
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
            $this->info("--------------------------------------------------");
            $this->info("Scraping de la page {$page} : {$url}");

            try {
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

                $productNodes->each(function (Crawler $node) {
                    try {
                        $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                        if ($titleNode->count() === 0) return;
                        $productName = trim($titleNode->text());

                        $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                        $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                        $imgNode = $node->filter('img');
                        $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                        // ----------------------------------------------------
                        // RÈGLE STRICTE : AUCUN PRODUIT SANS PHOTO
                        // ----------------------------------------------------
                        if (!$imgUrl) {
                            $this->line("    [IGNORÉ - Pas d'URL image] {$productName}");
                            return;
                        }

                        $description = "Produit importé depuis Togo Informatique";
                        $sousCatName = "Ordinateurs Portables";

                        if ($productUrl) {
                            try {
                                $prodResponse = Http::timeout(15)->get($productUrl);
                                if ($prodResponse->successful()) {
                                    $prodCrawler = new Crawler($prodResponse->body());
                                    
                                    $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                                    if ($descNode->count() > 0) {
                                        $description = trim($descNode->text());
                                    }

                                    $breadcrumbNode = $prodCrawler->filter('.woocommerce-breadcrumb, nav.woocommerce-breadcrumb, .pathway, .breadcrumbs');
                                    if ($breadcrumbNode->count() > 0) {
                                        $crumbs = $breadcrumbNode->filter('a');
                                        if ($crumbs->count() >= 2) {
                                            $sousCatName = trim($crumbs->last()->text());
                                        }
                                    }
                                }
                            } catch (\Exception $ex) {}
                        }

                        // Téléchargement de l'image
                        $photoPath = null;
                        try {
                            $imageContents = @file_get_contents($imgUrl);
                            if ($imageContents) {
                                $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                                Storage::disk('public')->put($imageName, $imageContents);
                                $photoPath = $imageName;
                            }
                        } catch (\Exception $ex) {}

                        // Si le téléchargement échoue, on ignore le produit
                        if (!$photoPath) {
                            $this->line("    [IGNORÉ - Échec téléchargement image] {$productName}");
                            return;
                        }
                        // ----------------------------------------------------

                        // IA / Logique de classification vers l'une des 6 grandes catégories
                        $mainCategoryName = $this->mapToMainCategory($productName . ' ' . $sousCatName);
                        
                        $category = Category::firstOrCreate(['nom' => $mainCategoryName]);

                        $sousCat = SousCategorie::firstOrCreate([
                            'category_id' => $category->id,
                            'nom' => $sousCatName
                        ]);

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

        $this->info('Scraping et classement intelligent de Togo Informatique terminés avec succès !');
    }

    /**
     * Système intelligent pour ranger un produit dans l'une des 6 grandes catégories.
     */
    private function mapToMainCategory(string $text): string
    {
        $name = mb_strtolower($text);

        // 1. Froid et Climatisation
        if (str_contains($name, 'climat') || str_contains($name, 'froid') || str_contains($name, 'ventilateur') || str_contains($name, 'split') || str_contains($name, 'refroidisseur')) {
            return 'Froid et Climatisation';
        }

        // 2. Caméra de surveillance & Sécurité
        if (str_contains($name, 'camera') || str_contains($name, 'securite') || str_contains($name, 'surveillance') || str_contains($name, 'alarme') || str_contains($name, 'interphone') || str_contains($name, 'dvr') || str_contains($name, 'nvr')) {
            return 'Caméra de surveillance & Sécurité';
        }

        // 3. Télécoms & Réseau
        if (str_contains($name, 'reseau') || str_contains($name, 'telecom') || str_contains($name, 'routeur') || str_contains($name, 'switch') || str_contains($name, 'wifi') || str_contains($name, 'fibre') || str_contains($name, 'cable')) {
            return 'Télécoms & Réseau';
        }

        // 4. Informatique (Par défaut pour ce site)
        if (str_contains($name, 'ordinateur') || str_contains($name, 'informatique') || str_contains($name, 'pc') || str_contains($name, 'laptop') || str_contains($name, 'ecran') || str_contains($name, 'clavier') || str_contains($name, 'imprimante') || str_contains($name, 'disque dur')) {
            return 'Informatique';
        }

        // 5. BTP
        if (str_contains($name, 'btp') || str_contains($name, 'construction') || str_contains($name, 'outillage') || str_contains($name, 'ciment')) {
            return 'BTP';
        }

        // 6. Électricité
        if (str_contains($name, 'electrique') || str_contains($name, 'electricite') || str_contains($name, 'lampe') || str_contains($name, 'disjoncteur')) {
            return 'Électricité';
        }

        return 'Informatique';
    }
}