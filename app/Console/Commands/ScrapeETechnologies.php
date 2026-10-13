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
    protected $description = 'Scrape et range intelligemment les produits avec photos et prix à NULL';

    public function handle()
    {
        $this->info('Début du scraping intelligent de E-technologies...');

        $baseUrl = 'https://etechnologies.shop/shop/';
        
        try {
            $response = Http::timeout(60)->retry(3, 1000)->get($baseUrl);
            if ($response->failed()) {
                $this->error("Impossible d'accéder à la boutique E-technologies.");
                return;
            }

            $crawler = new Crawler($response->body());

            // 1. Récupération des catégories du site distant
            $categoryLinks = [];
            $crawler->filter('.product-categories a, .widget_product_categories a, .woo-categories a, nav.woocommerce-breadcrumb a, .menu-item a')->each(function (Crawler $node) use (&$categoryLinks) {
                $href = $node->attr('href');
                $name = trim($node->text());
                $name = preg_replace('/\s*\(\d+\)\s*$/', '', $name);

                if ($href && $name && !in_array($href, array_keys($categoryLinks))) {
                    if (str_contains($href, 'product-category')) {
                        $categoryLinks[$href] = $name;
                    }
                }
            });

            if (empty($categoryLinks)) {
                $categoryLinks[$baseUrl] = "Catalogue Général";
            }

            $this->info("=> " . count($categoryLinks) . " catégories détectées sur le site source.");

            // 2. Parcourir chaque catégorie scrapée
            foreach ($categoryLinks as $catUrl => $scrapedCatName) {
                $this->info("--------------------------------------------------");
                $this->info("Traitement de la catégorie source : {$scrapedCatName}");

                // IA / Logique de classification vers l'une de vos 6 grandes catégories
                $mainCategoryName = $this->mapToMainCategory($scrapedCatName);
                
                // Récupère ou associe à l'une de vos 6 grandes catégories principales
                $category = Category::firstOrCreate(['nom' => $mainCategoryName]);

                $page = 1;
                $hasNextPage = true;

                while ($hasNextPage && $page <= 20) {
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

                        $this->info("   -> Page {$page} (" . $productNodes->count() . " produits)...");

                        $productNodes->each(function (Crawler $node) use ($category, $scrapedCatName) {
                            try {
                                $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                                if ($titleNode->count() === 0) return;
                                $productName = trim($titleNode->text());

                                $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                                $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                                $imgNode = $node->filter('img');
                                $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                                $description = "Produit importé depuis E-technologies";
                                $sousCatName = $scrapedCatName; // La catégorie d'origine devient la sous-catégorie

                                // Fiche produit détaillée
                                if ($productUrl) {
                                    try {
                                        $prodResponse = Http::timeout(15)->get($productUrl);
                                        if ($prodResponse->successful()) {
                                            $prodCrawler = new Crawler($prodResponse->body());
                                            
                                            $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                                            if ($descNode->count() > 0) {
                                                $description = trim($descNode->text());
                                            }

                                            // Affiner la sous-catégorie via le fil d'Ariane
                                            $breadcrumbNode = $prodCrawler->filter('.woocommerce-breadcrumb, nav.woocommerce-breadcrumb');
                                            if ($breadcrumbNode->count() > 0) {
                                                $crumbs = $breadcrumbNode->filter('a');
                                                if ($crumbs->count() >= 2) {
                                                    $sousCatName = trim($crumbs->last()->text());
                                                }
                                            }
                                        }
                                    } catch (\Exception $ex) {
                                        // Ignore l'erreur de la fiche individuelle
                                    }
                                }

                                // ----------------------------------------------------
                                // RÈGLE STRICTE : AUCUN PRODUIT SANS PHOTO
                                // ----------------------------------------------------
                                if (!$imgUrl) {
                                    $this->line("    [IGNORÉ - Pas d'URL image] {$productName}");
                                    return;
                                }

                                $photoPath = null;
                                try {
                                    $imageContents = @file_get_contents($imgUrl);
                                    if ($imageContents) {
                                        $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                                        Storage::disk('public')->put($imageName, $imageContents);
                                        $photoPath = $imageName;
                                    }
                                } catch (\Exception $ex) {
                                    // Échec du téléchargement
                                }

                                // Si le téléchargement de l'image a échoué, on ignore le produit
                                if (!$photoPath) {
                                    $this->line("    [IGNORÉ - Échec téléchargement image] {$productName}");
                                    return;
                                }
                                // ----------------------------------------------------

                                // Création de la sous-catégorie rattachée à la grande catégorie
                                $sousCat = SousCategorie::firstOrCreate([
                                    'category_id' => $category->id,
                                    'nom' => $sousCatName
                                ]);

                                // Enregistrement du produit avec prix à NULL
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

                                $this->line("    [SUCCÈS] {$productName} -> Rrangé dans [{$category->nom} > {$sousCat->nom}]");

                            } catch (\Exception $e) {
                                // Ignore un produit individuel en erreur
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

            $this->info('Scraping et classement intelligent terminés avec succès !');

        } catch (\Exception $e) {
            $this->error("Erreur générale : " . $e->getMessage());
        }
    }

    /**
     * Système intelligent de correspondance pour ranger chaque nom de catégorie 
     * dans l'une des 6 grandes catégories obligatoires.
     */
    private function mapToMainCategory(string $scrapedName): string
    {
        $name = mb_strtolower($scrapedName);

        // 1. Froid et Climatisation
        if (str_contains($name, 'climat') || str_contains($name, 'froid') || str_contains($name, 'ventilateur') || str_contains($name, 'split') || str_contains($name, 'refroidisseur')) {
            return 'Froid et Climatisation';
        }

        // 2. Caméra de surveillance & Sécurité
        if (str_contains($name, 'camera') || str_contains($name, 'securite') || str_contains($name, 'surveillance') || str_contains($name, 'alarme') || str_contains($name, 'interphone') || str_contains($name, 'dvr') || str_contains($name, 'nvr')) {
            return 'Caméra de surveillance & Sécurité';
        }

        // 3. Télécoms & Réseau
        if (str_contains($name, 'reseau') || str_contains($name, 'telecom') || str_contains($name, 'routeur') || str_contains($name, 'switch') || str_contains($name, 'wifi') || str_contains($name, 'fibre') || str_contains($name, 'cables réseau')) {
            return 'Télécoms & Réseau';
        }

        // 4. Informatique
        if (str_contains($name, 'ordinateur') || str_contains($name, 'informatique') || str_contains($name, 'pc') || str_contains($name, 'laptop') || str_contains($name, 'ecran') || str_contains($name, 'clavier') || str_contains($name, 'imprimante') || str_contains($name, 'laptop')) {
            return 'Informatique';
        }

        // 5. BTP
        if (str_contains($name, 'btp') || str_contains($name, 'construction') || str_contains($name, 'materiel') || str_contains($name, 'outillage') || str_contains($name, 'ciment')) {
            return 'BTP';
        }

        // 6. Électricité (Par défaut ou mots-clés spécifiques)
        return 'Électricité';
    }
}