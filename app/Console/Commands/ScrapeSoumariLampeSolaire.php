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

class ScrapeSoumariLampeSolaire extends Command
{
    protected $signature = 'soumari:scrape';
    protected $description = 'Scrape et range intelligemment les produits de Soumari (Énergie Solaire) avec photos et prix à NULL';

    public function handle()
    {
        $this->info('Début du scraping global de Soumari (Lampe Solaire)...');

        $baseUrl = 'https://www.soumari.com/categorie-produit/energie-solaire/lampe-solaire/';
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

                        // Extraction intelligente et robuste de l'URL de l'image
                        $imgNode = $node->filter('img');
                        $imgUrl = null;
                        if ($imgNode->count() > 0) {
                            $imgUrl = $imgNode->attr('data-src')
                                   ?: ($imgNode->attr('data-large_image')
                                   ?: ($imgNode->attr('src') ?: null));
                        }

                        // Si l'URL est relative, on la complète
                        if ($imgUrl && str_starts_with($imgUrl, '/')) {
                            $imgUrl = 'https://www.soumari.com' . $imgUrl;
                        }

                        // ----------------------------------------------------
                        // RÈGLE STRICTE : AUCUN PRODUIT SANS PHOTO
                        // ----------------------------------------------------
                        if (!$imgUrl) {
                            $this->line("    [IGNORÉ - Pas d'URL image] {$productName}");
                            return;
                        }

                        $description = "Produit importé depuis Soumari";
                        $sousCatName = "Lampe Solaire";

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

                        // Téléchargement sécurisé de l'image avec User-Agent et Referer
                        $photoPath = null;
                        try {
                            $imageResponse = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                                'Referer' => 'https://www.soumari.com/',
                            ])->timeout(15)->get($imgUrl);

                            if ($imageResponse->successful()) {
                                $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                                Storage::disk('public')->put($imageName, $imageResponse->body());
                                $photoPath = $imageName;
                            }
                        } catch (\Exception $ex) {}

                        // Si le téléchargement échoue, on ignore le produit
                        if (!$photoPath) {
                            $this->line("    [IGNORÉ - Échec téléchargement image] {$productName}");
                            return;
                        }
                        // ----------------------------------------------------

                        // IA / Logique de classification (Centrée sur Énergie Solaire)
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

        $this->info('Scraping et classement intelligent de Soumari terminés avec succès !');
    }

    /**
     * Système intelligent pour ranger un produit dans les catégories.
     */
    private function mapToMainCategory(string $text): string
    {
        $name = mb_strtolower($text);

        // 1. Énergie Solaire (Prioritaire pour Soumari)
        if (str_contains($name, 'solaire') || str_contains($name, 'panneau') || str_contains($name, 'batterie') || str_contains($name, 'onduleur') || str_contains($name, 'lampe')) {
            return 'Énergie Solaire';
        }

        // 2. Froid et Climatisation
        if (str_contains($name, 'climat') || str_contains($name, 'froid') || str_contains($name, 'ventilateur') || str_contains($name, 'split') || str_contains($name, 'refroidisseur')) {
            return 'Froid et Climatisation';
        }

        // 3. Caméra de surveillance & Sécurité
        if (str_contains($name, 'camera') || str_contains($name, 'securite') || str_contains($name, 'surveillance') || str_contains($name, 'alarme') || str_contains($name, 'interphone')) {
            return 'Caméra de surveillance & Sécurité';
        }

        // 4. Télécoms & Réseau
        if (str_contains($name, 'reseau') || str_contains($name, 'telecom') || str_contains($name, 'routeur') || str_contains($name, 'switch') || str_contains($name, 'wifi')) {
            return 'Télécoms & Réseau';
        }

        // 5. Informatique
        if (str_contains($name, 'ordinateur') || str_contains($name, 'informatique') || str_contains($name, 'pc') || str_contains($name, 'laptop') || str_contains($name, 'ecran') || str_contains($name, 'imprimante')) {
            return 'Informatique';
        }

        // 6. Électricité
        if (str_contains($name, 'electrique') || str_contains($name, 'electricite') || str_contains($name, 'disjoncteur') || str_contains($name, 'cable')) {
            return 'Électricité';
        }

        return 'Énergie Solaire';
    }
}
