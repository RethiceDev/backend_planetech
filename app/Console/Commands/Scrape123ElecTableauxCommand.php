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

class Scrape123ElecTableauxCommand extends Command
{
    protected $signature = 'scrape:123elec-tableaux';
    protected $description = 'Scrape les tableaux électriques depuis 123elec avec gestion robuste des images';

    public function handle()
    {
        $this->info("Début du scraping des tableaux électriques sur 123elec...");

        $electricite = Category::firstOrCreate([
            'nom' => 'Électricité Générale'
        ]);

        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $electricite->id,
            'nom' => 'Matériel BT (Basse Tension)'
        ]);

        $baseUrl = 'https://www.123elec.com/gamme-materiel-electrique/tableaux-electriques.html';
        $totalImported = 0;

        for ($page = 1; $page <= 10; $page++) {
            $url = $page === 1 ? $baseUrl : $baseUrl . '?p=' . $page;
            $this->info("\n--- Page {$page} : {$url} ---");

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->get($url);

            if ($response->failed()) {
                $this->warn(" -> Erreur HTTP (Code : " . $response->status() . ").");
                break;
            }

            $crawler = new Crawler($response->body());
            $nodes = $crawler->filter('.product-item, li.item.product, [data-product-id]');
            $this->info(" -> Produits détectés dans le HTML : " . $nodes->count());

            if ($nodes->count() === 0) {
                break;
            }

            $nodes->each(function (Crawler $node) use ($sousCategorie, &$totalImported) {
                try {
                    // 1. Nom du produit
                    $name = '';
                    foreach (['h1', 'h2', 'h3', 'strong', 'a'] as $tag) {
                        $filtered = $node->filter($tag);
                        foreach ($filtered as $el) {
                            $txt = trim($el->textContent);
                            if (strlen($txt) > 10 && !str_contains(strtolower($txt), 'ajouter') && !str_contains(strtolower($txt), 'stock')) {
                                $name = $txt;
                                break 2;
                            }
                        }
                    }

                    if (empty($name)) {
                        return;
                    }

                    // 2. Récupération de l'image (comparable à la logique ProTech)
                    $imgNode = $node->filter('img');
                    $imageUrl = null;
                    if ($imgNode->count() > 0) {
                        foreach (['data-src', 'src', 'data-image', 'data-lazy-src'] as $attr) {
                            $val = $imgNode->attr($attr);
                            if ($val && !str_starts_with($val, 'data:image') && !str_contains($val, 'placeholder')) {
                                $imageUrl = $val;
                                break;
                            }
                        }
                    }

                    $localImagePath = null;
                    if ($imageUrl) {
                        if (str_starts_with($imageUrl, '//')) {
                            $imageUrl = 'https:' . $imageUrl;
                        }

                        try {
                            // Utilisation d'un User-Agent explicite pour simuler un vrai navigateur lors du téléchargement de l'image
                            $responseImg = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                                'Referer' => 'https://www.123elec.com/'
                            ])->timeout(10)->get($imageUrl);

                            if ($responseImg->successful() && !empty($responseImg->body())) {
                                $imageName = Str::slug(substr($name, 0, 40)) . '-' . time() . '.jpg';
                                
                                // Sauvegarde dans le disque public (storage/app/public/products/)
                                Storage::disk('public')->put('products/' . $imageName, $responseImg->body());
                                
                                // Chemin stocké en base
                                $localImagePath = 'storage/products/' . $imageName;
                            }
                        } catch (\Exception $ex) {
                            // En cas d'échec du téléchargement, on laisse l'image à null ou une image par défaut
                        }
                    }

                    // 3. Prix
                    $priceText = '';
                    $priceElements = $node->filter('.price, span[data-price-amount], .price-wrapper');
                    if ($priceElements->count() > 0) {
                        $priceText = $priceElements->text();
                    } else {
                        $priceText = $node->text();
                    }

                    preg_match('/([\d\s,\.]+)\s*€/', $priceText, $matches);
                    $price = null;
                    if (isset($matches[1])) {
                        $cleanPrice = str_replace([' ', ','], ['', '.'], $matches[1]);
                        $price = is_numeric($cleanPrice) ? (float)$cleanPrice : null;
                    }

                    // 4. Enregistrement en base
                    $product = Product::firstOrCreate(
                        [
                            'sous_categorie_id' => $sousCategorie->id,
                            'name' => $name
                        ],
                        [
                            'price' => $price,
                            'photo' => $localImagePath,
                            'description' => "Tableau électrique et équipement modulaire de qualité professionnelle disponible sur 123elec.",
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );

                    $totalImported++;
                    $imgStatus = $localImagePath ? "[Img OK]" : "[Sans Img]";
                    $this->line("    [✔] Inséré : " . substr($name, 0, 40) . "... {$imgStatus}");
                } catch (\Exception $e) {
                    $this->error("    [X] Erreur : " . $e->getMessage());
                }
            });

            $nextPage = $crawler->filter('.pages-item-next a, a.action.next, [rel="next"]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Total traité : {$totalImported}");
    }
}