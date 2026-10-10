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

class ScrapeDataCommExpress extends Command
{
    protected $signature = 'datacommexpress:scrape';
    protected $description = 'Scrape les produits depuis Data Comm Express';

    public function handle()
    {
        $this->info('Début du scraping de Data Comm Express...');

        $url = 'https://datacommexpress.com/?srsltid=AU7gw4U4u8PU9zAa1svuUP9acTxlbCEcK9nI_gUNWzeuma5NUWymnlPi';

        // Créer une catégorie et une sous-catégorie par défaut
        $category = Category::firstOrCreate(['nom' => 'Matériel Informatique']);
        $sousCat = SousCategorie::firstOrCreate([
            'category_id' => $category->id,
            'nom' => 'Data Comm Express'
        ]);

        $response = Http::timeout(30)->get($url);

        if ($response->failed()) {
            $this->error("Impossible d'accéder au site Data Comm Express.");
            return;
        }

        $crawler = new Crawler($response->body());

        // Analyse des produits sur la page (adaptation aux structures e-commerce courantes)
        $crawler->filter('ul.products li.product, .product-item, .type-product')->each(function (Crawler $node) use ($sousCat) {
            try {
                // Nom du produit
                $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                if ($titleNode->count() === 0) return;
                $productName = trim($titleNode->text());

                // Lien vers la fiche produit
                $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                // Prix
                $priceNode = $node->filter('.price, .amount');
                $price = null;
                if ($priceNode->count() > 0) {
                    $priceText = preg_replace('/[^0-9.,]/', '', $priceNode->text());
                    $price = $priceText ? floatval(str_replace(',', '.', $priceText)) : null;
                }

                // Image
                $imgNode = $node->filter('img');
                $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                $description = "Produit importé depuis Data Comm Express";
                $photoPath = null;

                // Récupération de la description détaillée si le lien existe
                if ($productUrl) {
                    $prodResponse = Http::timeout(15)->get($productUrl);
                    if ($prodResponse->successful()) {
                        $prodCrawler = new Crawler($prodResponse->body());
                        $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                        if ($descNode->count() > 0) {
                            $description = trim($descNode->text());
                        }
                    }
                }

                // Téléchargement de l'image
                if ($imgUrl) {
                    $imageContents = @file_get_contents($imgUrl);
                    if ($imageContents) {
                        $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                        Storage::disk('public')->put($imageName, $imageContents);
                        $photoPath = $imageName;
                    }
                }

                // Enregistrement en base de données
                Product::firstOrCreate(
                    [
                        'name' => $productName,
                        'sous_categorie_id' => $sousCat->id
                    ],
                    [
                        'description' => $description,
                        'photo' => $photoPath,
                        'price' => $price,
                        'stock' => true
                    ]
                );

                $this->info("   -> [PRODUIT AJOUTÉ] {$productName} | Prix: " . ($price ?? 'N/C'));

            } catch (\Exception $e) {
                // Ignorer les erreurs d'un produit individuel pour continuer la boucle
            }
        });

        $this->info('Scraping de Data Comm Express terminé avec succès !');
    }
}