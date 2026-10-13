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

class ScrapeAsmaMaroc extends Command
{
    protected $signature = 'asmamaroc:scrape';
    protected $description = 'Scrape récursivement avec photos, prix à NULL et classement dans les 6 grandes catégories';

    public function handle()
    {
        $this->info('Début du scraping récursif intelligent de asmamamaroc.com...');

        $baseUrl = 'https://asmamamaroc.com';
        $response = Http::timeout(30)->get($baseUrl);

        if ($response->failed()) {
            $this->error("Impossible d'accéder au site principal.");
            return;
        }

        $crawler = new Crawler($response->body());

        // 1. Récupérer les catégories principales depuis le menu de la page d'accueil
        $crawler->filter('a')->each(function (Crawler $node) use ($baseUrl) {
            $name = trim($node->text());
            $url = $node->attr('href');

            if (empty($name) || empty($url)) return;

            if (str_contains($url, '/categorie-produit/') && $url !== $baseUrl.'/') {
                $this->info("Catégorie source détectée : {$name}");
                
                // Classification intelligente vers l'une des 6 grandes catégories
                $mainCategoryName = $this->mapToMainCategory($name);
                $category = Category::firstOrCreate(['nom' => $mainCategoryName]);

                // Lancer l'exploration récursive
                $this->recursiveCrawl($url, $category, null, 0);
            }
        });

        $this->info('Scraping récursif et classement intelligent terminés avec succès !');
    }

    private function recursiveCrawl($url, $category, $parentSousCat, $depth)
    {
        // Sécurité anti-boucle infinie (maximum 4 niveaux)
        if ($depth > 4) return;

        $response = Http::timeout(30)->get($url);
        if ($response->failed()) return;

        $crawler = new Crawler($response->body());
        $foundSubItems = false;

        // Analyser les éléments de la page (catégories filles ou produits)
        $crawler->filter('ul.products li.product, .product-item, .type-product, li.product-category')->each(function (Crawler $node) use ($category, $parentSousCat, $depth, $url, &$foundSubItems) {
            try {
                $linkNode = $node->filter('a.woocommerce-LoopProduct-link, a.button, a');
                if ($linkNode->count() === 0) return;

                $targetUrl = $linkNode->attr('href');
                $titleNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title, .woocommerce-loop-category__title');
                $title = $titleNode->count() > 0 ? trim($titleNode->text()) : trim($node->text());

                if (empty($targetUrl) || empty($title) || $targetUrl === $url) return;

                // Si c'est un sous-dossier / sous-catégorie
                if (str_contains($targetUrl, '/categorie-produit/')) {
                    $foundSubItems = true;
                    
                    // Créer dynamiquement la sous-catégorie rattachée à la grande catégorie
                    $currentSousCat = SousCategorie::firstOrCreate([
                        'category_id' => $category->id,
                        'nom' => $title
                    ]);

                    $this->line("   -> Sous-catégorie trouvée : {$title}");
                    
                    usleep(300000); // Pause de courtoisie

                    // Descendre d'un niveau
                    $this->recursiveCrawl($targetUrl, $category, $currentSousCat, $depth + 1);
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs mineures
            }
        });

        // Si on est arrivé au bout de l'arborescence, on extrait les produits
        if (!$foundSubItems && $parentSousCat) {
            $this->extractProductsFromPage($crawler, $parentSousCat);
        }
    }

    private function extractProductsFromPage(Crawler $crawler, $sousCat)
    {
        $crawler->filter('ul.products li.product')->each(function (Crawler $node) use ($sousCat) {
            try {
                if ($node->filter('.product-category')->count() > 0) return;

                $productNameNode = $node->filter('.woocommerce-loop-product__title, h2, h3, .product-title');
                if ($productNameNode->count() === 0) return;

                $productName = trim($productNameNode->text());
                
                $productLinkNode = $node->filter('a.woocommerce-LoopProduct-link, a');
                $productUrl = $productLinkNode->count() > 0 ? $productLinkNode->attr('href') : null;

                $imgNode = $node->filter('img');
                $imgUrl = $imgNode->count() > 0 ? ($imgNode->attr('data-src') ?: $imgNode->attr('src')) : null;

                // ----------------------------------------------------
                // RÈGLE STRICTE : AUCUN PRODUIT SANS PHOTO
                // ----------------------------------------------------
                if (!$imgUrl) {
                    $this->line("    [IGNORÉ - Pas d'URL image] {$productName}");
                    return;
                }

                $description = "Importé automatiquement depuis Asma Maroc";
                $photoPath = null;

                if ($productUrl) {
                    try {
                        $prodResponse = Http::timeout(15)->get($productUrl);
                        if ($prodResponse->successful()) {
                            $prodCrawler = new Crawler($prodResponse->body());
                            $descNode = $prodCrawler->filter('.woocommerce-product-details__short-description, .description, .entry-content');
                            if ($descNode->count() > 0) {
                                $description = trim($descNode->text());
                            }
                        }
                    } catch (\Exception $e) {}
                }

                try {
                    $imageContents = @file_get_contents($imgUrl);
                    if ($imageContents) {
                        $imageName = 'products/' . Str::slug($productName) . '-' . time() . '.jpg';
                        Storage::disk('public')->put($imageName, $imageContents);
                        $photoPath = $imageName;
                    }
                } catch (\Exception $e) {}

                // Si le téléchargement échoue, on ignore le produit
                if (!$photoPath) {
                    $this->line("    [IGNORÉ - Échec téléchargement image] {$productName}");
                    return;
                }
                // ----------------------------------------------------

                Product::firstOrCreate(
                    [
                        'name' => $productName,
                        'sous_categorie_id' => $sousCat->id
                    ],
                    [
                        'description' => $description,
                        'photo' => $photoPath,
                        'price' => null, // Strictement NULL
                        'stock' => true
                    ]
                );

                $this->line("      -> [PRODUIT CLASSÉ] {$productName} (Sous-cat: {$sousCat->nom})");

            } catch (\Exception $e) {
                // Ignorer
            }
        });
    }

    /**
     * Système intelligent pour mapper n'importe quelle catégorie vers l'une des 6 grandes catégories.
     */
    private function mapToMainCategory(string $text): string
    {
        $name = mb_strtolower($text);

        if (str_contains($name, 'climat') || str_contains($name, 'froid') || str_contains($name, 'ventilateur') || str_contains($name, 'split') || str_contains($name, 'refroidisseur')) {
            return 'Froid et Climatisation';
        }

        if (str_contains($name, 'camera') || str_contains($name, 'securite') || str_contains($name, 'surveillance') || str_contains($name, 'alarme') || str_contains($name, 'interphone') || str_contains($name, 'dvr') || str_contains($name, 'nvr')) {
            return 'Caméra de surveillance & Sécurité';
        }

        if (str_contains($name, 'reseau') || str_contains($name, 'telecom') || str_contains($name, 'routeur') || str_contains($name, 'switch') || str_contains($name, 'wifi') || str_contains($name, 'fibre') || str_contains($name, 'cable')) {
            return 'Télécoms & Réseau';
        }

        if (str_contains($name, 'ordinateur') || str_contains($name, 'informatique') || str_contains($name, 'pc') || str_contains($name, 'laptop') || str_contains($name, 'ecran') || str_contains($name, 'clavier') || str_contains($name, 'imprimante') || str_contains($name, 'disque')) {
            return 'Informatique';
        }

        if (str_contains($name, 'btp') || str_contains($name, 'construction') || str_contains($name, 'outillage') || str_contains($name, 'ciment')) {
            return 'BTP';
        }

        return 'Électricité';
    }
}