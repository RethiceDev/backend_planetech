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

class ScrapeCablesReseauTogoCommand extends Command
{
    protected $signature = 'scrape:cables-reseau-togo';
    protected $description = 'Scrape les rouleaux de câbles Ethernet RJ45 et accessoires réseau depuis Togo Informatique';

    public function handle()
    {
        $this->info("Début du scraping des câbles et rouleaux réseau...");

        // 1. Récupération / Création de la catégorie principale
        $reseaux = Category::firstOrCreate([
            'nom' => 'Réseaux & Télécoms'
        ]);

        // 2. Ciblage de la sous-catégorie dédiée aux câbles
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $reseaux->id,
            'nom' => 'Câbles Réseau et Télécoms'
        ]);

        $baseUrl = 'https://www.togoinformatique.com/product-category/reseau-informatique/consommables-reseau/cable-reseau/';
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
            $this->info("Scraping de la page {$page} : {$url}");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ])->get($url);

            if ($response->failed()) {
                $this->warn(" -> Impossible d'accéder à la page {$page} (Code: " . $response->status() . ").");
                break;
            }

            $crawler = new Crawler($response->body());
            $products = $crawler->filter('li.product, .product-item, .item-product, article.product');
            $this->info(" -> Produits détectés sur cette page : " . $products->count());

            if ($products->count() === 0) {
                $this->line(" -> Aucun produit trouvé sur cette page.");
                break;
            }

            $products->each(function (Crawler $node) use ($sousCategorie) {
                try {
                    $nomNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title a, a.woocommerce-LoopProduct-link');
                    if ($nomNode->count() === 0) return;
                    $name = trim($nomNode->text());
                    if (empty($name)) return;

                    // Filtrage de sécurité
                    $lowerName = mb_strtolower($name);
                    if (str_contains($lowerName, 'core') || str_contains($lowerName, 'ddr') || str_contains($lowerName, 'laptop')) {
                        return;
                    }

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
                    $description = $descNode->count() > 0 ? trim($descNode->text()) : "Rouleau de câble Ethernet RJ45 disponible sur devis.";

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

            // Gestion de la pagination WooCommerce
            $nextPage = $crawler->filter('a.next.page-numbers');
            if ($nextPage->count() === 0 || $page >= 10) {
                $hasMorePages = false;
            } else {
                $page++;
            }
        }

        $this->info("Scraping des câbles réseau terminé avec succès !");
    }
}
