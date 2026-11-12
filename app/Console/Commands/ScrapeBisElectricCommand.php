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

class ScrapeBisElectricCommand extends Command
{
    protected $signature = 'scrape:bis-electric-dpn';
    protected $description = 'Scrape les disjoncteurs DPN depuis Bis-Electric';

    public function handle()
    {
        $this->info("Début du scraping des disjoncteurs DPN sur Bis-Electric...");

        // 1. Catégorie et sous-catégorie
        $electricite = Category::firstOrCreate([
            'nom' => 'Électricité Générale'
        ]);

        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $electricite->id,
            'nom' => 'Matériel BT (Basse Tension)'
        ]);

        $baseUrl = 'https://www.bis-electric.com/modulaire/disjoncteur-electrique/disjoncteur-dpn.html';
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages && $page <= 10) {
            $url = $page === 1 ? $baseUrl : $baseUrl . '?p=' . $page;
            $this->info("Scraping de la page {$page} : {$url}");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            ])->get($url);

            if ($response->failed()) {
                $this->warn(" -> Impossible d'accéder à la page {$page} (Code: " . $response->status() . ").");
                break;
            }

            $crawler = new Crawler($response->body());
            
            // Sélecteurs Magento pour les cartes produits
            $products = $crawler->filter('.product-item, li.item.product');
            $this->info(" -> Produits détectés sur cette page : " . $products->count());

            if ($products->count() === 0) {
                $this->line(" -> Aucun produit trouvé ou fin de pagination atteinte.");
                break;
            }

            $importedCount = 0;

            breadcrumbs:
            $products->each(function (Crawler $node) use ($sousCategorie, &$importedCount) {
                try {
                    // Nom du produit
                    $nomNode = $node->filter('.product-item-link, a.product-item-link');
                    if ($nomNode->count() === 0) return;
                    $name = trim($nomNode->text());
                    if (empty($name)) return;

                    // Image du produit
                    $imgNode = $node->filter('.product-image-photo, img');
                    $imageUrl = null;
                    if ($imgNode->count() > 0) {
                        foreach (['data-src', 'src'] as $attr) {
                            $val = $imgNode->attr($attr);
                            if ($val && !str_starts_with($val, 'data:image') && !str_contains($val, 'placeholder')) {
                                $imageUrl = $val;
                                break;
                            }
                        }
                    }

                    // Téléchargement de l'image en local
                    $localImagePath = null;
                    if ($imageUrl) {
                        try {
                            $responseImg = Http::timeout(10)->withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                            ])->get($imageUrl);

                            if ($responseImg->successful() && !empty($responseImg->body())) {
                                $imageName = Str::slug(substr($name, 0, 40)) . '-' . time() . '.jpg';
                                Storage::disk('public')->put('products/' . $imageName, $responseImg->body());
                                $localImagePath = 'storage/products/' . $imageName;
                            }
                        } catch (\Exception $ex) {
                            // Ignorer l'erreur d'image
                        }
                    }

                    // Prix
                    $priceNode = $node->filter('.price, .price-wrapper');
                    $price = null;
                    if ($priceNode->count() > 0) {
                        $priceText = preg_replace('/[^\d.,]/', '', str_replace(',', '.', $priceNode->text()));
                        $price = is_numeric($priceText) ? (float)$priceText : null;
                    }

                    // Enregistrement en base de données
                    $product = Product::firstOrCreate(
                        [
                            'sous_categorie_id' => $sousCategorie->id,
                            'name' => $name
                        ],
                        [
                            'price' => $price,
                            'photo' => $localImagePath,
                            'description' => "Disjoncteur DPN de qualité professionnelle disponible sur Bis-Electric.",
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );

                    if ($product->wasRecentlyCreated) {
                        $importedCount++;
                        $this->line("    -> Importé : {$name}");
                    }
                } catch (\Exception $e) {
                    // Ignorer les erreurs d'items individuels
                }
            });

            // Gestion de la pagination Magento (bouton "Suivant" / .pages-item-next)
            $nextPage = $crawler->filter('.pages-item-next a, a.action.next');
            if ($nextPage->count() === 0) {
                $hasMorePages = false;
            } else {
                $page++;
            }

            sleep(1);
        }

        $this->info("Scraping des disjoncteurs DPN terminé avec succès !");
    }
}