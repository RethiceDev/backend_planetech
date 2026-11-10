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

class ScrapeImprimantesCommand extends Command
{
    protected $signature = 'scrape:imprimantes';
    protected $description = 'Scrape les imprimantes et les associe à la sous-catégorie Impression et Consommables';

    public function handle()
    {
        $this->info("Début du scraping des imprimantes (Impression et Consommables)...");

        // 1. Récupération de la catégorie principale
        $informatique = Category::firstOrCreate([
            'nom' => 'Informatique et Bureautique'
        ]);

        // 2. Ciblage précis de la sous-catégorie demandée
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $informatique->id,
            'nom' => 'Impression et Consommables'
        ]);

        $page = 1;
        $baseUrl = 'https://www.togoinformatique.com/product-category/imprimantes-et-scanners/imprimante-blanc-noir/';
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

                    // Extraction robuste de l'URL distante de l'image
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

                    // Téléchargement local de l'image pour éviter les erreurs 404 / liens expirés
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
                    $description = $descNode->count() > 0 ? trim($descNode->text()) : "Imprimante noir et blanc disponible sur devis.";

                    // Enregistrement sécurisé en base de données
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

            // Gestion de la pagination WooCommerce
            $nextPage = $crawler->filter('a.next.page-numbers');
            if ($nextPage->count() === 0 || $page >= 10) {
                $hasMorePages = false;
            } else {
                $page++;
            }
        }

        $this->info("Scraping des imprimantes terminé avec succès !");
    }
}
