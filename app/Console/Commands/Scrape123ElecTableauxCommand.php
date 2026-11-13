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
    protected $description = 'Scrape les tableaux électriques 123elec en récupérant les images via les pages de détails';

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
        $totalProcessed = 0;
        $totalUpdated = 0;

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
                $this->line(" -> Plus de produits détectés, fin de la pagination.");
                break;
            }

            foreach ($nodes as $i => $domElement) {
                try {
                    $node = new Crawler($domElement);

                    // 1. Extraction du nom et du lien de la page produit
                    $name = '';
                    $productUrl = '';

                    $linkNode = $node->filter('a.product-item-photo, a.product.photo, a[href*=".html"]');
                    if ($linkNode->count() > 0) {
                        $productUrl = $linkNode->attr('href');
                    }

                    foreach (['h1', 'h2', 'h3', 'strong', 'a'] as $tag) {
                        $filtered = $node->filter($tag);
                        if ($filtered->count() > 0) {
                            foreach ($filtered as $el) {
                                $txt = trim($el->textContent);
                                if (strlen($txt) > 10 && !str_contains(strtolower($txt), 'ajouter') && !str_contains(strtolower($txt), 'stock')) {
                                    $name = $txt;
                                    break 2;
                                }
                            }
                        }
                    }

                    if (empty($name)) {
                        continue;
                    }

                    // Vérifier si le produit existe déjà en base
                    $product = Product::where('sous_categorie_id', $sousCategorie->id)
                                      ->where('name', $name)
                                      ->first();

                    if ($product && !empty($product->photo)) {
                        $totalProcessed++;
                        continue;
                    }

                    // 2. Visiter la page de détail du produit pour récupérer la vraie image haute résolution
                    $imageUrl = null;
                    if (!empty($productUrl)) {
                        try {
                            $productPageResponse = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                                'Referer' => $baseUrl
                            ])->timeout(8)->get($productUrl);

                            if ($productPageResponse->successful()) {
                                $productCrawler = new Crawler($productPageResponse->body());
                                // Sélecteurs courants pour l'image principale sur une fiche produit Magento / e-commerce
                                $imgDetail = $productCrawler->filter('.gallery-image, .fotorama__img, .product-image-photo, .main-image img, .product-image img');

                                if ($imgDetail->count() > 0) {
                                    foreach ($imgDetail as $imgEl) {
                                        $imgCrawlerSingle = new Crawler($imgEl);
                                        foreach (['src', 'data-src', 'data-large_image', 'srcset'] as $attr) {
                                            if ($imgEl->hasAttribute($attr)) {
                                                $val = trim($imgEl->getAttribute($attr));
                                                if ($attr === 'srcset') {
                                                    $parts = explode(',', $val);
                                                    $val = trim(explode(' ', trim($parts[0]))[0]);
                                                }
                                                if (!empty($val) && !str_contains($val, 'placeholder') && !str_starts_with($val, 'data:image')) {
                                                    $imageUrl = $val;
                                                    break 2;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            // En cas d'échec de la page détail, on continue
                        }
                    }

                    $localImagePath = null;
                    if ($imageUrl) {
                        if (str_starts_with($imageUrl, '//')) {
                            $imageUrl = 'https:' . $imageUrl;
                        } elseif (str_starts_with($imageUrl, '/')) {
                            $imageUrl = 'https://www.123elec.com' . $imageUrl;
                        }

                        try {
                            $responseImg = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                                'Referer' => $productUrl
                            ])->timeout(10)->get($imageUrl);

                            if ($responseImg->successful() && !empty($responseImg->body())) {
                                $imageName = Str::slug(substr($name, 0, 40)) . '-' . time() . '-' . rand(100, 999) . '.jpg';
                                Storage::disk('public')->put('products/' . $imageName, $responseImg->body());
                                $localImagePath = 'storage/products/' . $imageName;
                            }
                        } catch (\Exception $ex) {
                            // Ignorer l'échec de téléchargement
                        }
                    }

                    // 3. Extraction du prix
                    $priceText = '';
                    $priceElements = $node->filter('.price, span[data-price-amount], .price-wrapper, .normal-price .price');
                    if ($priceElements->count() > 0) {
                        $priceText = $priceElements->text();
                    } else {
                        $priceText = $node->text();
                    }

                    preg_match('/([\d\s,\.]+)\s*€/', $priceText, $matches);
                    $price = null;
                    if (isset($matches[1])) {
                        $cleanPrice = str_replace([' ', ','], ['', '.'], trim($matches[1]));
                        $price = is_numeric($cleanPrice) ? (float)$cleanPrice : null;
                    }

                    // 4. Enregistrement en base de données
                    if ($product) {
                        $product->update([
                            'price' => $price ?? $product->price,
                            'photo' => $localImagePath ?? $product->photo,
                        ]);
                        $totalUpdated++;
                        $this->line("    [🔄] Mis à jour : " . substr($name, 0, 40) . "... " . ($localImagePath ? "[Photo Ajoutée]" : "[Sans Photo]"));
                    } else {
                        Product::create([
                            'sous_categorie_id' => $sousCategorie->id,
                            'name' => $name,
                            'price' => $price,
                            'photo' => $localImagePath,
                            'description' => "Tableau électrique et équipement modulaire de qualité professionnelle disponible sur 123elec.",
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $totalUpdated++;
                        $this->line("    [✔] Inséré : " . substr($name, 0, 40) . "... " . ($localImagePath ? "[Avec Photo]" : "[Sans Photo]"));
                    }

                    $totalProcessed++;
                } catch (\Exception $e) {
                    $this->error("    [X] Erreur : " . $e->getMessage());
                }
            }

            $nextPage = $crawler->filter('.pages-item-next a, a.action.next, [rel="next"]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Total traité : {$totalProcessed} | Total mis à jour : {$totalUpdated}");
    }
}
