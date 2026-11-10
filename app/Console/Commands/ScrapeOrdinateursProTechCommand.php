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

class ScrapeOrdinateursProTechCommand extends Command
{
    protected $signature = 'scrape:ordinateurs-protech';
    protected $description = 'Scrape les ordinateurs portables et de bureau depuis ProTech Togo avec pagination forcée';

    public function handle()
    {
        $this->info("Début du scraping des ordinateurs depuis ProTech Togo...");

        // 1. Récupération de la catégorie principale
        $informatique = Category::firstOrCreate([
            'nom' => 'Informatique et Bureautique'
        ]);

        // 2. Ciblage de la sous-catégorie ordinateurs
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $informatique->id,
            'nom' => 'Ordinateurs Portables et PC de Bureau'
        ]);

        $baseUrl = 'https://protechtogo.com/product-category/ordinateurs-pc/';

        // Boucle explicite sur 10 pages maximum (ajustable si besoin)
        for ($page = 1; $page <= 10; $page++) {
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
            $this->info("Scraping de la page {$page} : {$url}");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.3',
            ])->get($url);

            // Si la page renvoie une erreur 404 ou autre, c'est qu'on a dépassé la dernière page
            if ($response->failed()) {
                $this->warn(" -> Fin de pagination atteinte (Code HTTP : " . $response->status() . ").");
                break;
            }

            $crawler = new Crawler($response->body());

            // Élargissement des sélecteurs pour thèmes WooCommerce / Elementor
            $products = $crawler->filter('li.product, .product-grid-item, .woo-product-item, .elementor-grid-item, .product');
            $this->info(" -> Produits détectés sur cette page : " . $products->count());

            // Si aucun produit n'est trouvé sur la page, on stoppe la boucle
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
                    $description = $descNode->count() > 0 ? trim($descNode->text()) : "Ordinateur disponible sur devis.";

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

        $this->info("Scraping de toutes les pages d'ordinateurs ProTech terminé avec succès !");
    }
}
