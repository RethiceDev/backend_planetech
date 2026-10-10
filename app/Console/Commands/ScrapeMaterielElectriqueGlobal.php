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

class ScrapeMaterielElectriqueGlobal extends Command
{
    protected $signature = 'materielelectrique:scrape-all';
    protected $description = 'Scrape tout le catalogue de materielelectrique.com catégorie par catégorie de façon sécurisée';

    public function handle()
    {
        $this->info('==================================================');
        $this->info('  DEBUT DU SCRAPING GLOBAL DE MATERIEL ELECTRIQUE ');
        $this->info('==================================================');

       $categoriesToScrape = [
        [
            'nom' => 'Accessoires de coffret de communication',
            'url' => 'https://www.materielelectrique.com/c/accessoires-de-coffret-de-communication-103493'
        ],
        [
            'nom' => 'Tableau électrique étanche',
            'url' => 'https://www.materielelectrique.com/c/tableau-electrique-etanche-103470'
        ],
        [
            'nom' => 'Mini tableau électrique',
            'url' => 'https://www.materielelectrique.com/c/mini-tableau-electrique-104584'
        ],
        [
            'nom' => 'Fil électrique rigide H07VU 1.5 à 2.5 mm2',
            'url' => 'https://www.materielelectrique.com/c/fil-electrique-rigide-ho7vu-1-5-a-2-5-mm2-111575'
        ],
        [
            'nom' => 'Fil électrique souple H05VK 0.75 à 1 mm2',
            'url' => 'https://www.materielelectrique.com/c/fil-electrique-souple-ho5vk-0-75-a-1-mm2-6404'
        ],
        [
            'nom' => 'Câble électrique rigide EDF RV-FV',
            'url' => 'https://www.materielelectrique.com/c/cable-electrique-rigide-edf-rvfv-6407'
        ],
        [
            'nom' => 'Spot encastré',
            'url' => 'https://www.materielelectrique.com/c/spot-encastre-111508'
        ],
        [
            'nom' => 'Ampoules et lampes à décharge',
            'url' => 'https://www.materielelectrique.com/c/ampoule-lampe-ampoule-lampe-a-decharge-100108'
        ],
        [
            'nom' => 'Interrupteur connecté',
            'url' => 'https://www.materielelectrique.com/c/interrupteur-connecte-111873'
        ],
        [
            'nom' => 'Chemin de câbles fil',
            'url' => 'https://www.materielelectrique.com/c/chemin-de-cables-fil-53172'
        ],
        [
            'nom' => 'Accessoires de fixation',
            'url' => 'https://www.materielelectrique.com/c/accessoires-fixation-53174'
        ],
        [
            'nom' => 'Conduit, gaine électrique et tube',
            'url' => 'https://www.materielelectrique.com/c/conduit-et-gaine-electrique-accessoires-gaine-electrique-et-tube-61619'
        ],
        [
            'nom' => 'Fibre optique (fil et câble)',
            'url' => 'https://www.materielelectrique.com/c/fibre-optique-fil-et-cable-103374'
        ],
        [
            'nom' => 'Fil électrique souple H07VK 1.5 à 95 mm2',
            'url' => 'https://www.materielelectrique.com/c/fil-electrique-souple-ho7vk-1-5-a-95-mm2-111576'
        ]
    ];
        foreach ($categoriesToScrape as $catData) {
            $this->info("\n--------------------------------------------------");
            $this->info("📂 Catégorie principale : " . $catData['nom']);
            $this->info("--------------------------------------------------");

            $mainCategory = Category::firstOrCreate(['nom' => $catData['nom']]);

            $page = 1;
            $hasNextPage = true;
            $categoryProductsCount = 0;

            while ($hasNextPage && $page <= 50) {
                $url = $page === 1 ? $catData['url'] : $catData['url'] . '?p=' . $page;

                $this->info("-> Scraping de la page {$page} : {$url}");

                try {
                    $response = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36')
                        ->withHeaders([
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                            'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                        ])
                        ->timeout(45)
                        ->retry(3, 3000)
                        ->get($url);

                    if ($response->failed()) {
                        $this->warn("   -> Impossible d'accéder à la page {$page}. Fin pour cette catégorie.");
                        break;
                    }

                    $crawler = new Crawler($response->body());
                    $productNodes = $crawler->filter('.product-item, .product-miniature, .item-product, .product-card, .list-product li');

                    if ($productNodes->count() === 0) {
                        $this->info("   -> Aucun produit supplémentaire sur la page {$page}. Fin de la catégorie.");
                        break;
                    }

                    $this->info("   -> " . $productNodes->count() . " produits trouvés sur cette page. Enregistrement...");

                    $productNodes->each(function (Crawler $node) use ($mainCategory, &$categoryProductsCount) {
                        try {
                            // Recherche élargie et sécurisée du titre
                            $productName = null;
                            $selectors = ['.product-title', '.product-name', 'h2 a', 'h3 a', 'a.product-link', '.h3.product-title a', 'a'];
                            
                            foreach ($selectors as $selector) {
                                $titleNode = $node->filter($selector);
                                if ($titleNode->count() > 0) {
                                    $text = trim($titleNode->text());
                                    // On s'assure que le texte récupéré est un vrai titre (pas juste un lien vide ou une image)
                                    if (!empty($text) && strlen($text) > 3) {
                                        $productName = $text;
                                        break;
                                    }
                                }
                            }

                            if (!$productName) {
                                return; // Ignore si aucun nom n'a pu être extrait
                            }

                            $linkNode = $node->filter('a');
                            $productUrl = $linkNode->count() > 0 ? $linkNode->attr('href') : null;
                            
                            if ($productUrl && !str_starts_with($productUrl, 'http')) {
                                $productUrl = 'https://www.materielelectrique.com' . $productUrl;
                            }

                            $priceNode = $node->filter('.price, .product-price, span.amount');
                            $price = null;
                            if ($priceNode->count() > 0) {
                                $priceText = preg_replace('/[^0-9.,]/', '', $priceNode->text());
                                $price = $priceText ? floatval(str_replace(',', '.', $priceText)) : null;
                            }

                            $imgNode = $node->filter('img');
                            $imgUrl = null;
                            if ($imgNode->count() > 0) {
                                $imgUrl = $imgNode->attr('data-src') 
                                    ?: ($imgNode->attr('data-lazy-src') ?: $imgNode->attr('src'));
                            }

                            $description = "Matériel électrique importé de materielelectrique.com";
                            $photoPath = null;
                            $sousCatName = $mainCategory->nom;

                            $sousCat = SousCategorie::firstOrCreate([
                                'category_id' => $mainCategory->id,
                                'nom' => $sousCatName
                            ]);

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

                            $categoryProductsCount++;
                            $this->info("   -> [AJOUTÉ] {$productName} | Prix: " . ($price ? $price . ' €' : 'N/C'));

                        } catch (\Exception $e) {}
                    });

                    $page++;
                    sleep(2);

                } catch (\Exception $e) {
                    $this->error("   -> Erreur réseau sur la page {$page} : " . $e->getMessage());
                    break;
                }
            }

            $this->info("✅ Fin de la catégorie '{$catData['nom']}'. Total de produits traités : {$categoryProductsCount}");
            $this->info("Pause de sécurité de 5 secondes avant la catégorie suivante...\n");
            sleep(5);
        }

        $this->info('==================================================');
        $this->info('  TOUT LE CATALOGUE A ETE SCRAPE AVEC SUCCES !    ');
        $this->info('==================================================');
    }
}