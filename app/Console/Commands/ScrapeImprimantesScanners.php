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

class ScrapeImprimantesScanners extends Command
{
    protected $signature = 'protechtogo:scrape-imprimantes';
    protected $description = 'Scrape et range intelligemment les imprimantes et scanners avec photos et prix à NULL';

    public function handle()
    {
        $this->info('Début du scraping intelligent de la catégorie Imprimantes & Scanners...');

        $baseUrl = 'https://protechtogo.com/product-category/imprimantes-scanners/';
        
        $page = 1;
        $hasNextPage = true;

        while ($hasNextPage && $page <= 20) {
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

                $productNodes->each(function (Crawler $node) {
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

                        // ----------------------------------------------------
                        // RÈGLE STRICTE : AUCUN PRODUIT SANS PHOTO
                        // ----------------------------------------------------
                        if (!$imgUrl) {
                            $this->line("    [IGNORÉ - Pas d'URL image] {$productName}");
                            return;
                        }

                        $description = "Produit importé depuis Protech Togo";
                        $scrapedSubCatName = "Imprimantes & Scanners";

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
                                            $scrapedSubCatName = trim($crumbs->last()->text());
                                        }
                                    }
                                }
                            } catch (\Exception $e) {}
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
                        } catch (\Exception $e) {}

                        // Si le téléchargement échoue, on ignore le produit
                        if (!$photoPath) {
                            $this->line("    [IGNORÉ - Échec téléchargement image] {$productName}");
                            return;
                        }
                        // ----------------------------------------------------

                        // IA / Logique de classification vers l'une des 6 grandes catégories ou Divers
                        $mainCategoryName = $this->mapToMainCategory($productName . ' ' . $scrapedSubCatName);
                        
                        $category = Category::firstOrCreate(['nom' => $mainCategoryName]);

                        $sousCat = SousCategorie::firstOrCreate([
                            'category_id' => $category->id,
                            'nom' => $scrapedSubCatName
                        ]);

                        // Enregistrement en base de données (Prix strictement forcé à NULL)
                        Product::firstOrCreate(
                            [
                                'name' => $productName,
                                'sous_categorie_id' => $sousCat->id
                            ],
                            [
                                'description' => $description,
                                'photo' => $photoPath,
                                'price' => null, // Forcé à NULL
                                'stock' => true
                            ]
                        );

                        $this->line("    [SUCCÈS] {$productName} -> [{$category->nom} > {$sousCat->nom}]");

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

        $this->info('Scraping et classement intelligent de la catégorie Imprimantes & Scanners terminés avec succès !');
    }

    /**
     * Système intelligent pour ranger un produit dans l'une des grandes catégories ou dans Divers.
     */
    private function mapToMainCategory(string $text): string
    {
        $name = mb_strtolower($text);

        if (str_contains($name, 'climat') || str_contains($name, 'froid') || str_contains($name, 'ventilateur') || str_contains($name, 'split') || str_contains($name, 'refroidisseur')) {
            return 'Froid et Climatisation';
        }

        if (str_contains($name, 'camera') || str_contains($name, 'securite') || str_contains($name, 'surveillance') || str_contains($name, 'alarme') || str_contains($name, 'interphone') || str_contains($name, 'dvr') || str_contains($name, 'nvr')) {
            return 'Caméra de surveillance & Sécurité';
        }

        if (str_contains($name, 'reseau') || str_contains($name, 'telecom') || str_contains($name, 'routeur') || str_contains($name, 'switch') || str_contains($name, 'wifi') || str_contains($name, 'fibre') || str_contains($name, 'cable')) {
            return 'Télécoms & Réseau';
        }

        if (str_contains($name, 'ordinateur') || str_contains($name, 'informatique') || str_contains($name, 'pc') || str_contains($name, 'laptop') || str_contains($name, 'ecran') || str_contains($name, 'clavier') || str_contains($name, 'imprimante') || str_contains($name, 'scanner') || str_contains($name, 'disque')) {
            return 'Informatique';
        }

        if (str_contains($name, 'btp') || str_contains($name, 'construction') || str_contains($name, 'outillage') || str_contains($name, 'ciment')) {
            return 'BTP';
        }

        if (str_contains($name, 'electrique') || str_contains($name, 'electricite') || str_contains($name, 'lampe') || str_contains($name, 'disjoncteur')) {
            return 'Électricité';
        }

        return 'Divers';
    }
}