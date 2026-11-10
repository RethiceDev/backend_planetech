<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\SousCategorie;
use App\Models\Product;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScrapeDiversProTechCommand extends Command
{
    protected $signature = 'scrape:divers-protech';
    protected $description = 'Scrape les produits divers depuis ProTech Togo dans la catégorie principale Divers';

    public function handle()
    {
        $this->info("Début du scraping des produits divers depuis ProTech Togo...");

        // 1. Création de la catégorie principale "Divers"
        $category = Category::firstOrCreate([
            'nom' => 'Divers'
        ]);

        // 2. Sous-catégorie par défaut "Divers" liée à la catégorie principale
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $category->id,
            'nom' => 'Divers'
        ]);

        $baseUrl = 'https://protechtogo.com/product-category/autres-informatiques/';

        // Boucle explicite sur 10 pages maximum
        for ($page = 1; $page <= 10; $page++) {
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
            $this->info("Scraping de la page {$page} : {$url}");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.3',
            ])->get($url);

            if ($response->failed()) {
                $this->warn(" -> Fin de pagination atteinte (Code HTTP : " . $response->status() . ").");
                break;
            }

            $crawler = new Crawler($response->body());

            $products = $crawler->filter('li.product, .product-grid-item, .woo-product-item, .elementor-grid-item, .product');
            $this->info(" -> Produits détectés sur cette page : " . $products->count());

            if ($products->count() === 0) {
                $this->line(" -> Aucun produit supplémentaire. Fin du parcours.");
                break;
            }

            $products->each(function (Crawler $node) use ($sousCategorie) {
                try {
                    $nomNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title a, a.woocommerce-LoopProduct-link, .elementor-heading-title');
                    if ($nomNode->count() === 0) return;
                    $name = trim($nomNode->text());
                    if (empty($name)) return;

                    // Extraction de l'URL de l'image
                    $imgNode = $node->filter('img');
                    $imageUrl = null;
                    if ($imgNode->count() > 0) {
                        foreach (['data-lazy-src', 'data-src', 'data-large_image', 'src'] as $attr) {
                            $val = $imgNode->attr($attr);
                            if ($val && !str_starts_with($val, 'data:image') && !str_contains($val, 'placeholder')) {
                                $imageUrl = $val;
                                break;
                            }
                        }
                    }

                    // Téléchargement local de l'image
                    $localImagePath = null;
                    if ($imageUrl) {
                        try {
                            $responseImg = Http::timeout(15)->withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                            ])->get($imageUrl);

                            if ($responseImg->successful() && !empty($responseImg->body())) {
                                $imageName = Str::slug($name) . '-' . time() . '.jpg';
                                Storage::disk('public')->put('products/' . $imageName, $responseImg->body());
                                $localImagePath = 'storage/products/' . $imageName;
                            }
                        } catch (\Exception $ex) {
                            // Ignorer l'erreur d'image
                        }
                    }

                    $descNode = $node->filter('.short-description, .product-details, p');
                    $description = $descNode->count() > 0 ? trim($descNode->text()) : "Produit divers disponible sur devis.";

                    // Enregistrement en base de données
                    Product::firstOrCreate(
                        [
                            'sous_categorie_id' => $sousCategorie->id,
                            'name' => $name
                        ],
                        [
                            'price' => null,
                            'photo' => $localImagePath,
                            'description' => $description,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );

                    $this->line("    -> Importé avec succès : {$name}");
                } catch (\Exception $e) {
                    $this->error("    -> Erreur insertion produit : " . $e->getMessage());
                }
            });
        }

        $this->info("Scraping des produits divers ProTech terminé avec succès !");
    }
}
