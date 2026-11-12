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

class ScrapeAlibabaRegulateursCommand extends Command
{
    protected $signature = 'scrape:regulateurs-alibaba';
    protected $description = 'Scrape les régulateurs de tension et matériel BT depuis Alibaba';

    public function handle()
    {
        $this->info("Début du scraping des régulateurs sur Alibaba...");

        // 1. Récupération / Création de la catégorie principale
        $electricite = Category::firstOrCreate([
            'nom' => 'Électricité Générale'
        ]);

        // 2. Ciblage de la sous-catégorie BT
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $electricite->id,
            'nom' => 'Matériel BT (Basse Tension)'
        ]);

        // URL de recherche Alibaba avec le terme "Régulateur de tension automatique"
        $baseUrl = 'https://www.alibaba.com/trade/search?fsb=y&IndexArea=product_en&SearchText=Automatic+Voltage+Regulator';
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages && $page <= 5) { // Limité à 5 pages pour éviter le blocage
            // Sur Alibaba, la pagination passe par le paramètre &page=
            $url = $page === 1 ? $baseUrl : $baseUrl . '&page=' . $page;
            $this->info("Scraping de la page {$page} : {$url}");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            ])->get($url);

            if ($response->failed()) {
                $this->warn(" -> Impossible d'accéder à la page {$page} (Code: " . $response->status() . ").");
                break;
            }

            $crawler = new Crawler($response->body());
            
            // Sélecteurs ciblant les cartes de produits sur les pages de recherche Alibaba
            $products = $crawler->filter('.list-no-v2-item, .organic-list-item, div[data-content="organic-product"], .item-card');
            $this->info(" -> Produits détectés sur cette page : " . $products->count());

            if ($products->count() === 0) {
                $this->line(" -> Aucun produit trouvé ou fin de pagination atteinte.");
                break;
            }

            // Variable pour suivre si on a trouvé de nouveaux produits sur cette page
            $importedCount = 0;

            $products->each(function (Crawler $node) use ($sousCategorie, &$importedCount) {
                try {
                    $nomNode = $node->filter('.elements-title-normal__content, h2 a, .title, .element-title');
                    if ($nomNode->count() === 0) return;
                    $name = trim($nomNode->text());
                    if (empty($name)) return;

                    // Extraction de l'image
                    $imgNode = $node->filter('img');
                    $imageUrl = null;
                    if ($imgNode->count() > 0) {
                        foreach (['data-lazy-src', 'data-src', 'src'] as $attr) {
                            $val = $imgNode->attr($attr);
                            if ($val && !str_starts_with($val, 'data:image') && !str_contains($val, 'placeholder')) {
                                $imageUrl = $val;
                                if (!str_starts_with($imageUrl, 'http')) {
                                    $imageUrl = 'https:' . $imageUrl;
                                }
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
                                $imageName = Str::slug(substr($name, 0, 50)) . '-' . time() . '.jpg';
                                Storage::disk('public')->put('products/' . $imageName, $responseImg->body());
                                $localImagePath = 'storage/products/' . $imageName;
                            }
                        } catch (\Exception $ex) {
                            // Ignorer l'erreur d'image
                        }
                    }

                    // Extraction du prix indicatif si disponible
                    $priceNode = $node->filter('.elements-offer-price-normal__price, .price');
                    $description = $priceNode->count() > 0 ? "Prix indicatif : " . trim($priceNode->text()) : "Régulateur de tension disponible sur commande.";

                    // Enregistrement en base de données
                    $product = Product::firstOrCreate(
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

                    if ($product->wasRecentlyCreated) {
                        $importedCount++;
                        $this->line("    -> Importé avec succès : {$name}");
                    }
                } catch (\Exception $e) {
                    // Ignorer les erreurs d'items individuels
                }
            });

            // Incrémentation de la page pour la boucle suivante
            $page++;
            
            // Pause de courtoisie entre les pages pour limiter les risques de blocage réseau
            sleep(2);
        }

        $this->info("Scraping des régulateurs Alibaba terminé avec succès !");
    }
}