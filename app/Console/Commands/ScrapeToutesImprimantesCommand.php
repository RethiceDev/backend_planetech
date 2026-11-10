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

class ScrapeToutesImprimantesCommand extends Command
{
    protected $signature = 'scrape:toutes-imprimantes';
    protected $description = 'Scrape toutes les sous-catégories d\'imprimantes et scanners (A4, A3, ppm) avec diagnostic';

    public function handle()
    {
        $this->info("Début du scraping global des imprimantes et scanners...");

        // 1. Récupération de la catégorie principale
        $informatique = Category::firstOrCreate([
            'nom' => 'Informatique et Bureautique'
        ]);

        // 2. Ciblage de la sous-catégorie
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $informatique->id,
            'nom' => 'Impression et Consommables'
        ]);

        // 3. Liste des URLs à scraper
        $urls = [
            'https://www.togoinformatique.com/product-category/imprimantes-et-scanners/format-a4/',
            'https://www.togoinformatique.com/product-category/imprimantes-et-scanners/format-a3/',
            'https://www.togoinformatique.com/product-category/imprimantes-et-scanners/15-ppm-20-ppm/',
            'https://www.togoinformatique.com/product-category/imprimantes-et-scanners/20-ppm-22-ppm/',
            'https://www.togoinformatique.com/product-category/imprimantes-et-scanners/27-ppm-38-ppm/',
        ];

        foreach ($urls as $baseUrl) {
            $this->info("Traitement de l'URL : {$baseUrl}");
            $page = 1;
            $hasMorePages = true;

            while ($hasMorePages) {
                $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
                $this->line(" -> Page {$page} : {$url}");

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                ])->get($url);

                if ($response->failed()) {
                    $this->warn(" -> Impossible d'accéder à la page {$page} (Code: " . $response->status() . ").");
                    break;
                }

                $crawler = new Crawler($response->body());

                // Sélecteur élargi pour capturer les produits WooCommerce peu importe la disposition
                $products = $crawler->filter('li.product, .product-item, .item-product, article.product');
                $this->info(" -> Produits détectés sur cette page : " . $products->count());

                if ($products->count() === 0) {
                    $this->line(" -> Aucun produit supplémentaire trouvé.");
                    break;
                }

                $products->each(function (Crawler $node) use ($sousCategorie) {
                    try {
                        // Ciblage élargi du titre du produit
                        $nomNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title a, a.woocommerce-LoopProduct-link');
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
                        $description = $descNode->count() > 0 ? trim($descNode->text()) : "Matériel d'impression disponible sur devis.";

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

                // Gestion de la pagination
                $nextPage = $crawler->filter('a.next.page-numbers');
                if ($nextPage->count() === 0 || $page >= 10) {
                    $hasMorePages = false;
                } else {
                    $page++;
                }
            }
        }

        $this->info("Scraping global de toutes les imprimantes terminé avec succès !");
    }
}
