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

class ScrapeEcransProjectionCommand extends Command
{
    protected $signature = 'scrape:ecrans-projection';
    protected $description = 'Scrape les écrans de projection depuis Togo Informatique';

    public function handle()
    {
        $this->info("Début du scraping des écrans de projection...");

        $informatique = Category::firstOrCreate(['nom' => 'Informatique et Bureautique']);
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $informatique->id,
            'nom' => 'Écrans et Périphériques'
        ]);

        $baseUrl = 'https://www.togoinformatique.com/product-category/video-projecteur/ecran-de-projection/';
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
            $this->info("Scraping de la page {$page} : {$url}");
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])->get($url);

            if ($response->failed()) {
                $this->warn(" -> Impossible d'accéder à la page {$page}.");
                break;
            }

            $crawler = new Crawler($response->body());
            $products = $crawler->filter('li.product, .product-item, .item-product, article.product');

            if ($products->count() === 0) {
                $this->line(" -> Aucun produit trouvé.");
                break;
            }

            $products->each(function (Crawler $node) use ($sousCategorie) {
                try {
                    $nomNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title a, a.woocommerce-LoopProduct-link');
                    if ($nomNode->count() === 0) return;
                    $name = trim($nomNode->text());
                    if (empty($name)) return;

                    $lowerName = mb_strtolower($name);
                    if (str_contains($lowerName, 'core') || str_contains($lowerName, 'ddr') || str_contains($lowerName, 'laptop')) return;

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
                            $responseImg = Http::timeout(15)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($imageUrl);
                            if ($responseImg->successful() && !empty($responseImg->body())) {
                                $imageName = Str::slug($name) . '-' . time() . '.jpg';
                                Storage::disk('public')->put('products/' . $imageName, $responseImg->body());
                                $localImagePath = 'storage/products/' . $imageName;
                            }
                        } catch (\Exception $ex) {}
                    }

                    $descNode = $node->filter('.short-description, .product-details, p');
                    $description = $descNode->count() > 0 ? trim($descNode->text()) : "Écran de projection disponible sur devis.";

                    Product::firstOrCreate(
                        ['sous_categorie_id' => $sousCategorie->id, 'name' => $name],
                        ['price' => null, 'photo' => $localImagePath, 'description' => $description, 'created_at' => now(), 'updated_at' => now()]
                    );

                    $this->line("    -> Importé : {$name}");
                } catch (\Exception $e) {}
            });

            $nextPage = $crawler->filter('a.next.page-numbers');
            if ($nextPage->count() === 0 || $page >= 10) {
                $hasMorePages = false;
            } else {
                $page++;
            }
        }
        $this->info("Terminé !");
    }
}
