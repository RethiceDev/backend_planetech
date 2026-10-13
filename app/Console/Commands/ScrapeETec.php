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

class ScrapeETec extends Command
{
    protected $signature = 'etechnologies:scrape';
    protected $description = 'Scrape les produits de etechnologies.shop avec photos, prix à NULL et classement intelligent';

    public function handle()
    {
        $this->info('Début du scraping intelligent de etechnologies.shop...');

        $baseUrl = 'https://etechnologies.shop';
        $response = Http::timeout(90)->get($baseUrl);

        if ($response->failed()) {
            $this->error("Impossible d'accéder à etechnologies.shop.");
            return;
        }

        $crawler = new Crawler($response->body());

        $crawler->filter('a')->each(function (Crawler $node) use ($baseUrl) {
            $url = $node->attr('href');
            $name = trim($node->text());

            if (empty($url) || empty($name)) return;

            if ((str_contains($url, '/product-category/') || str_contains($url, '/boutique/') || str_contains($url, '/shop/')) && $url !== $baseUrl) {
                $this->line("Exploration de la section : {$name}");
                
                usleep(500000); 

                $this->scrapeProductsFromPage($url, $name);
            }
        });

        $this->info('Scraping et classement intelligent de etechnologies.shop terminés avec succès !');
    }

    private function scrapeProductsFromPage($url, $subCatName)
    {
        $response = Http::timeout(90)->get($url);
        if ($response->failed()) return;

        $crawler = new Crawler($response->body());

        // IA / Logique de classification vers l'une des 6 grandes catégories
        $mainCategoryName = $this->mapToMainCategory($subCatName);
        $category = Category::firstOrCreate(['nom' => $mainCategoryName]);

        $sousCat = SousCategorie::firstOrCreate([
            'category_id' => $category->id,
            'nom' => $subCatName ?: 'Général'
        ]);

        $crawler->filter('ul.products li.product, .product-item, .type-product')->each(function (Crawler $node) use ($category, $sousCat) {
            try {
                if ($node->filter('.product-category')->count() > 0) return;

                $productNameNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                if ($productNameNode->count() === 0) return;

                $productName = trim($productNameNode->text());
                
                $productLinkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                $productUrl = $productLinkNode->count() > 0 ? $productLinkNode->attr('href') : null;

                $imgNode = $node->filter('img');
                $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                // ----------------------------------------------------
                // RÈGLE STRICTE : AUCUN PRODUIT SANS PHOTO
                // ----------------------------------------------------
                if (!$imgUrl) {
                    $this->line("    [IGNORÉ - Pas d'URL image] {$productName}");
                    return;
                }

                $description = "Importé automatiquement depuis E-Technologies";
                $photoPath = null;

                if ($productUrl) {
                    try {
                        $prodResponse = Http::timeout(30)->get($productUrl);
                        if ($prodResponse->successful()) {
                            $prodCrawler = new Crawler($prodResponse->body());
                            $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                            if ($descNode->count() > 0) {
                                $description = trim($descNode->text());
                            }
                        }
                    } catch (\Exception $e) {}
                }

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

                $this->line("    -> [PRODUIT CLASSÉ] {$productName} [{$category->nom} > {$sousCat->nom}]");

            } catch (\Exception $e) {
                // Ignorer les erreurs mineures
            }
        });
    }

    /**
     * Système intelligent pour mapper n'importe quel nom vers l'une des 6 grandes catégories.
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

        if (str_contains($name, 'ordinateur') || str_contains($name, 'informatique') || str_contains($name, 'pc') || str_contains($name, 'laptop') || str_contains($name, 'ecran') || str_contains($name, 'clavier') || str_contains($name, 'imprimante') || str_contains($name, 'disque')) {
            return 'Informatique';
        }

        if (str_contains($name, 'btp') || str_contains($name, 'construction') || str_contains($name, 'outillage') || str_contains($name, 'ciment')) {
            return 'BTP';
        }

        return 'Électricité';
    }
}