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

class ScrapMultimediaProTechCommand extends Command
{
    protected $signature = 'scrape:multimedia-protech';
    protected $description = 'Scrape les moniteurs, vidéoprojecteurs et écrans de projection depuis ProTech Togo';

    public function handle()
    {
        $this->info("Début du scraping multimédia et affichage depuis ProTech Togo...");

        // 1. Récupération de la catégorie principale
        $informatique = Category::firstOrCreate([
            'nom' => 'Informatique et Bureautique'
        ]);

        // 2. Ciblage de la sous-catégorie correspondante
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $informatique->id,
            'nom' => 'Écrans et Périphériques'
        ]);

        // 3. Liste des URLs multimédia à scraper
        $urls = [
            'https://protechtogo.com/product-category/multimedia/moniteurs-ecran/',
            'https://protechtogo.com/product-category/video-projecteur/',
            'https://protechtogo.com/product-category/multimedia/ecran-de-projection/',
        ];

        foreach ($urls as $baseUrl) {
            $this->info("Traitement de l'URL : {$baseUrl}");

            for ($page = 1; $page <= 10; $page++) {
                $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
                $this->line(" -> Page {$page} : {$url}");

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.3',
                ])->get($url);

                if ($response->failed()) {
                    $this->warn(" -> Fin de pagination atteinte ou erreur (Code HTTP : " . $response->status() . ").");
                    break;
                }

                $crawler = new Crawler($response->body());
                $products = $crawler->filter('li.product, .product-grid-item, .woo-product-item, .elementor-grid-item, .product');
                $this->info(" -> Produits détectés : " . $products->count());

                if ($products->count() === 0) {
                    $this->line(" -> Aucun produit supplémentaire sur cette section.");
                    break;
                }

                $products->each(function (Crawler $node) use ($sousCategorie) {
                    try {
                        $nomNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title a, a.woocommerce-LoopProduct-link, .elementor-heading-title');
                        if ($nomNode->count() === 0) return;
                        $name = trim($nomNode->text());
                        if (empty($name)) return;

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
                            } catch (\Exception $ex) {}
                        }

                        $descNode = $node->filter('.short-description, .product-details, p');
                        $description = $descNode->count() > 0 ? trim($descNode->text()) : "Matériel multimédia disponible sur devis.";

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
        }

        $this->info("Scraping global multimédia ProTech terminé avec succès !");
    }
}
