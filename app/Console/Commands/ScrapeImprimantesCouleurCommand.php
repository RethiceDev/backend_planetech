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

class ScrapeImprimantesCouleurCommand extends Command
{
    protected $signature = 'scrape:imprimantes-couleur';
    protected $description = 'Scrape les imprimantes couleur et les associe à Impression et Consommables';

    public function handle()
    {
        $this->info("Début du scraping des imprimantes couleur...");

        // 1. Récupération de la catégorie principale
        $informatique = Category::firstOrCreate([
            'nom' => 'Informatique et Bureautique'
        ]);

        // 2. Ciblage précis de la sous-catégorie
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $informatique->id,
            'nom' => 'Impression et Consommables'
        ]);

        $page = 1;
        $baseUrl = 'https://www.togoinformatique.com/product-category/imprimantes-et-scanners/imprimante-couleur/';
        $hasMorePages = true;

        while ($hasMorePages) {
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
            $this->info("Scraping de la page : {$url}");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ])->get($url);

            if ($response->failed()) {
                $this->error("Erreur lors de l'accès à la page {$page}");
                break;
            }

            $crawler = new Crawler($response->body());
            $products = $crawler->filter('li.product');

            if ($products->count() === 0) {
                $this->info("Plus aucun produit trouvé sur cette page.");
                $hasMorePages = false;
                break;
            }

            $products->each(function (Crawler $node) use ($sousCategorie) {
                try {
                    $nomNode = $node->filter('.woocommerce-loop-product__title, h2, h3');
                    if ($nomNode->count() === 0) return;
                    $name = trim($nomNode->text());

                    // Extraction de l'URL de l'image
                    $imgNode = $node->filter('.woocommerce-loop-product__link img, .attachment-woocommerce_thumbnail, img');
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
                            $this->warn(" -> Erreur téléchargement image pour {$name}");
                        }
                    }

                    $descNode = $node->filter('.short-description, .product-details, p');
                    $description = $descNode->count() > 0 ? trim($descNode->text()) : "Imprimante couleur disponible sur devis.";

                    // Enregistrement en base
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

                    $this->line("-> Importé : {$name} [Image: " . ($localImagePath ? 'OK' : 'ABSENTE') . "]");
                } catch (\Exception $e) {
                    $this->error("Erreur sur un produit : " . $e->getMessage());
                }
            });

            // Pagination WooCommerce
            $nextPage = $crawler->filter('a.next.page-numbers');
            if ($nextPage->count() === 0 || $page >= 10) {
                $hasMorePages = false;
            } else {
                $page++;
            }
        }

        $this->info("Scraping des imprimantes couleur terminé avec succès !");
    }
}
