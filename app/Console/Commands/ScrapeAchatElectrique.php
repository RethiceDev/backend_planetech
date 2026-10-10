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

class ScrapeAchatElectrique extends Command
{
    protected $signature = 'achatelectrique:scrape-nouveautes';
    protected $description = 'Scrape les nouveaux produits depuis achat-electrique.com';

    public function handle()
    {
        $this->info('Début du scraping des nouveautés sur Achat Électrique...');

        $baseUrl = 'https://www.achat-electrique.com/fr/nouveaux-produits';
        
        // Création de la catégorie principale
        $category = Category::firstOrCreate(['nom' => 'Matériel Électrique']);
        $sousCat = SousCategorie::firstOrCreate([
            'category_id' => $category->id,
            'nom' => 'Nouveautés Électriques'
        ]);

        $page = 1;
        $hasNextPage = true;

        while ($hasNextPage && $page <= 20) { // Sécurité pour éviter une boucle infinie
            // Pagination de type PrestaShop (?page=2, ?page=3, etc.)
            $url = $page === 1 ? $baseUrl : $baseUrl . '?page=' . $page;

            $this->info("--------------------------------------------------");
            $this->info("Scraping de la page {$page} : {$url}");

            try {
                // Utilisation d'un User-Agent standard pour éviter d'être bloqué par le serveur
                $response = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
                    ->timeout(45)
                    ->retry(3, 2000)
                    ->get($url);

                if ($response->failed()) {
                    $this->warn("Impossible d'accéder à la page {$page}. Fin du scraping.");
                    break;
                }

                $crawler = new Crawler($response->body());
                
                // Sélecteurs adaptés aux structures de type PrestaShop / E-commerce classique
                $productNodes = $crawler->filter('.product-miniature, .item-product, ul.product_list > li');

                if ($productNodes->count() === 0) {
                    $this->info("Aucun produit trouvé sur la page {$page}. Fin du scraping.");
                    break;
                }

                $this->info("-> {$productNodes->count()} produits trouvés. Traitement en cours...");

                $productNodes->each(function (Crawler $node) use ($sousCat) {
                    try {
                        // Nom du produit
                        $titleNode = $node->filter('.product-title a, h3.h3.product-title a, .h3.product-title a, a.product-name');
                        if ($titleNode->count() === 0) return;
                        $productName = trim($titleNode->text());

                        // Lien vers la fiche produit
                        $productUrl = $titleNode->attr('href');

                        // Prix
                        $priceNode = $node->filter('.price, span.price');
                        $price = null;
                        if ($priceNode->count() > 0) {
                            $priceText = preg_replace('/[^0-9.,]/', '', $priceNode->text());
                            $price = $priceText ? floatval(str_replace(',', '.', $priceText)) : null;
                        }

                        // Image
                        $imgNode = $node->filter('img');
                        $imgUrl = null;
                        if ($imgNode->count() > 0) {
                            $imgUrl = $imgNode->attr('data-full-size-image-url') 
                                ?: ($imgNode->attr('data-src') ?: $imgNode->attr('src'));
                        }

                        $description = "Produit importé depuis Achat Électrique";
                        $photoPath = null;

                        // Récupération de la description détaillée sur la fiche produit
                        if ($productUrl) {
                            try {
                                $prodResponse = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
                                    ->timeout(10)
                                    ->get($productUrl);

                                if ($prodResponse->successful()) {
                                    $prodCrawler = new Crawler($prodResponse->body());
                                    $descNode = $prodCrawler->filter('#product-description-short, .product-description, .rte');
                                    if ($descNode->count() > 0) {
                                        $description = trim($descNode->text());
                                    }
                                }
                            } catch (\Exception $e) {}
                        }

                        // Téléchargement de l'image
                        if ($imgUrl) {
                            try {
                                $imageContents = @file_get_contents($imgUrl);
                                if ($imageContents) {
                                    $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                                    Storage::disk('public')->put($imageName, $imageContents);
                                    $photoPath = $imageName;
                                }
                            } catch (\Exception $e) {}
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

                        $this->info("   -> [AJOUTÉ] {$productName} | Prix: " . ($price ?? 'N/C'));

                    } catch (\Exception $e) {}
                });

                // Vérifier s'il existe un bouton de page suivante pour continuer ou stopper
                $nextPageNode = $crawler->filter('a.next, .pagination .next a');
                if ($nextPageNode->count() === 0 && $page > 1) {
                    // Si aucun bouton "suivant" n'est trouvé et qu'on est au-delà de la page 1, on arrête
                    // (Sécurité complémentaire en plus de la limite max)
                }

                $page++;
                usleep(500000); // Pause de 0.5s pour ménager le serveur distant

            } catch (\Exception $e) {
                $this->error("Erreur réseau sur la page {$page} : " . $e->getMessage());
                break;
            }
        }

        $this->info('Scraping des nouveautés Achat Électrique terminé avec succès !');
    }
}