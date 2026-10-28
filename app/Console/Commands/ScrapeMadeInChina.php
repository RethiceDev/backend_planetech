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

class ScrapeMadeInChina extends Command
{
    protected $signature = 'madeinchina:scrape';
    protected $description = 'Scrape les produits depuis Made-in-China avec photos et prix à NULL';

    public function handle()
    {
        $this->info('Début du scraping de Made-in-China...');

        $baseUrl = 'https://fr.made-in-china.com/tag_search_product/Cable_uosn_1.html?pv_id=1k61agblle7a&faw_id=1k61ahet162b&bv_id=1k61ahet9c68&pbv_id=1k61ag8ui9dcj';

        $this->info("--------------------------------------------------");
        $this->info("Scraping de : {$baseUrl}");

        try {
            // Simulation d'un navigateur réel avec en-têtes complets
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'no-cache',
            ])->timeout(30)->get($baseUrl);

            if ($response->failed()) {
                $this->error("Erreur HTTP Code: " . $response->status());
                return;
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            // Sélecteurs étendus utilisés par Made-In-China pour leurs cartes produits
            $selectors = [
                '.product-info',
                '.sr-sideListing-item',
                '.product-node',
                '.prod-item',
                'div[data-node-name="product-item"]',
                '.product-card'
            ];

            $productNodes = null;
            foreach ($selectors as $selector) {
                $nodes = $crawler->filter($selector);
                if ($nodes->count() > 0) {
                    $productNodes = $nodes;
                    $this->info("Sélecteur fonctionnel trouvé : {$selector} ({$nodes->count()} produits)");
                    break;
                }
            }

            if (!$productNodes || $productNodes->count() === 0) {
                $this->warn("Aucun élément trouvé. Analyse de la structure reçue...");

                // Sauvegarde temporaire du HTML pour vérifier ce que le serveur renvoie
                file_put_contents(storage_path('logs/debug_madeinchina.html'), $html);
                $this->line("Le HTML de la page a été enregistré dans storage/logs/debug_madeinchina.html pour inspection.");
                return;
            }

            $count = 0;
            $productNodes->each(function (Crawler $node) use (&$count) {
                try {
                    // Extraction du titre
                    $titleNode = $node->filter('.product-name, .title, h2, h3, .prod-name, a[title]');
                    if ($titleNode->count() === 0) return;
                    $productName = trim($titleNode->text());

                    // Extraction de l'image
                    $imgNode = $node->filter('img');
                    $imgUrl = null;
                    if ($imgNode->count() > 0) {
                        $imgUrl = $imgNode->attr('data-original')
                               ?: $imgNode->attr('data-src')
                               ?: $imgNode->attr('src')
                               ?: $imgNode->attr('data-lazyload-src');
                    }

                    if (!$imgUrl) return;

                    if (str_starts_with($imgUrl, '//')) {
                        $imgUrl = 'https:' . $imgUrl;
                    }

                    // Téléchargement de l'image
                    $photoPath = null;
                    try {
                        $imageContents = @file_get_contents($imgUrl);
                        if ($imageContents) {
                            $imageName = 'products/cable-' . Str::slug(Str::limit($productName, 40, '')) . '-' . time() . rand(100,999) . '.jpg';
                            Storage::disk('public')->put($imageName, $imageContents);
                            $photoPath = $imageName;
                        }
                    } catch (\Exception $ex) {}

                    if (!$photoPath) return;

                    // Catégorisation
                    $category = Category::firstOrCreate(['nom' => 'Électricité']);
                    $sousCat = SousCategorie::firstOrCreate([
                        'category_id' => $category->id,
                        'nom' => 'Moteur Électrique'
                    ]);

                    Product::firstOrCreate(
                        [
                            'name' => $productName,
                            'sous_categorie_id' => $sousCat->id
                        ],
                        [
                            'description' => "Matériel électrique / Câble importé",
                            'photo' => $photoPath,
                            'price' => null,
                            'stock' => true
                        ]
                    );

                    $count++;
                    $this->line("    [SUCCÈS {$count}] {$productName}");

                } catch (\Exception $e) {}
            });

            $this->info("Scraping terminé : {$count} produits enregistrés avec succès !");

        } catch (\Exception $e) {
            $this->error("Erreur lors de l'exécution : " . $e->getMessage());
        }
    }
}
