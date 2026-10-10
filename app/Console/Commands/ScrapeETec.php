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
    protected $description = 'Scrape les produits de etechnologies.shop et les range dans la catégorie Telecom';

    public function handle()
    {
        $this->info('Début du scraping de etechnologies.shop...');

        $category = Category::firstOrCreate(['nom' => 'Telecom']);
        $baseUrl = 'https://etechnologies.shop';
        
        // Timeout à 90 secondes pour la page d'accueil
        $response = Http::timeout(90)->get($baseUrl);

        if ($response->failed()) {
            $this->error("Impossible d'accéder à etechnologies.shop.");
            return;
        }

        $crawler = new Crawler($response->body());

        $crawler->filter('a')->each(function (Crawler $node) use ($category, $baseUrl) {
            $url = $node->attr('href');
            $name = trim($node->text());

            if (empty($url) || empty($name)) return;

            if ((str_contains($url, '/product-category/') || str_contains($url, '/boutique/') || str_contains($url, '/shop/')) && $url !== $baseUrl) {
                $this->line("Exploration de la section : {$name}");
                
                // Petite pause pour ne pas saturer le serveur
                usleep(500000); 

                $this->scrapeProductsFromPage($url, $category, $name);
            }
        });

        $this->info('Scraping de etechnologies.shop terminé avec succès !');
    }

    private function scrapeProductsFromPage($url, $category, $subCatName)
    {
        // Timeout augmenté à 90 secondes pour éviter le cURL error 28 sur les grosses pages (ex: fibres-optiques)
        $response = Http::timeout(90)->get($url);
        if ($response->failed()) return;

        $crawler = new Crawler($response->body());

        $sousCat = SousCategorie::firstOrCreate([
            'category_id' => $category->id,
            'nom' => $subCatName ?: 'Général Telecom'
        ]);

        $crawler->filter('ul.products li.product, .product-item, .type-product')->each(function (Crawler $node) use ($sousCat) {
            try {
                if ($node->filter('.product-category')->count() > 0) return;

                $productNameNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                if ($productNameNode->count() === 0) return;

                $productName = trim($productNameNode->text());
                
                $productLinkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                $productUrl = $productLinkNode->count() > 0 ? $productLinkNode->attr('href') : null;

                $imgNode = $node->filter('img');
                $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                $description = "Importé automatiquement depuis E-Technologies";
                $photoPath = null;

                if ($productUrl) {
                    $prodResponse = Http::timeout(30)->get($productUrl);
                    if ($prodResponse->successful()) {
                        $prodCrawler = new Crawler($prodResponse->body());
                        $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                        if ($descNode->count() > 0) {
                            $description = trim($descNode->text());
                        }
                    }
                }

                if ($imgUrl) {
                    $imageContents = @file_get_contents($imgUrl);
                    if ($imageContents) {
                        $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                        Storage::disk('public')->put($imageName, $imageContents);
                        $photoPath = $imageName;
                    }
                }

                Product::firstOrCreate(
                    [
                        'name' => $productName,
                        'sous_categorie_id' => $sousCat->id
                    ],
                    [
                        'description' => $description,
                        'photo' => $photoPath,
                        'price' => null,
                        'stock' => true
                    ]
                );

                $this->info("    -> Produit Telecom enregistré : {$productName}");

            } catch (\Exception $e) {
                // Ignorer les erreurs mineures
            }
        });
    }
}