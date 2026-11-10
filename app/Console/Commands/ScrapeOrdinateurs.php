<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\SousCategorie;
use App\Models\Product;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;

class ScrapeOrdinateurs extends Command
{
    protected $signature = 'scrape:ordinateurs';
    protected $description = 'Scrape les ordinateurs depuis Togo Informatique';

    public function handle()
    {
        $this->info("Début du scraping des ordinateurs...");

        $informatique = Category::firstOrCreate([
            'nom' => 'Informatique et Bureautique'
        ]);

        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $informatique->id,
            'nom' => 'Ordinateurs Portables et PC de Bureau'
        ]);

        $page = 1;
        $baseUrl = 'https://www.togoinformatique.com/product-category/ordinateurs/';
        $hasMorePages = true;

        while ($hasMorePages) {
            $url = $page === 1 ? $baseUrl : $baseUrl . 'page/' . $page . '/';
            $this->info("Scraping de la page : {$url}");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ])->get($url);

            if ($response->failed()) {
                $this->error("Erreur lors de l'accès à la page {$page}");
                break;
            }

            $crawler = new Crawler($response->body());
            $products = $crawler->filter('li.product');

            if ($products->count() === 0) {
                $this->info("Plus aucun produit trouvé sur cette page.");
                $hasMorePages = false;
                break;
            }

            $products->each(function (Crawler $node) use ($sousCategorie) {
                try {
                    // Nom du produit
                    $nomNode = $node->filter('.woocommerce-loop-product__title, h2, h3');
                    if ($nomNode->count() === 0) return;
                    $name = trim($nomNode->text());

                    // Image du produit
                    $imgNode = $node->filter('img');
                    $imageUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?? $imgNode->attr('src')) : null;

                    // Récupération d'une description courte si elle existe sur la carte, sinon texte par défaut
                    $descNode = $node->filter('.short-description, .product-details, p');
                    $description = $descNode->count() > 0 ? trim($descNode->text()) : "Ordinateur portable ou PC de bureau disponible sur devis.";

                    // Enregistrement sécurisé avec gestion des champs obligatoires
                    Product::firstOrCreate(
                        [
                            'sous_categorie_id' => $sousCategorie->id,
                            'name' => $name
                        ],
                        [
                            'price' => null,
                            'photo' => $imageUrl,
                            'description' => $description,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );

                    $this->line("-> Importé : {$name}");
                } catch (\Exception $e) {
                    $this->error("Erreur sur un produit ({$name}): " . $e->getMessage());
                }
            });

            $nextPage = $crawler->filter('a.next.page-numbers');
            if ($nextPage->count() === 0 || $page >= 11) {
                $hasMorePages = false;
            } else {
                $page++;
            }
        }

        $this->info("Scraping des ordinateurs terminé avec succès !");
    }
}
