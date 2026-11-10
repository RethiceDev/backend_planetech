<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Category;
use App\Models\Product;
use App\Models\SousCategorie;
use Illuminate\Support\Str;

class ScrapeDeepTableauCommand extends Command
{
    protected $signature = 'scrape:deep-tableau {url}';
    protected $description = 'Scrape en profondeur et classe minutieusement les produits sous Électricité Générale';

    public function handle()
    {
        $startUrl = $this->argument('url');
        $this->info("=== DÉBUT DU SCRAPING ET RANGEMENT STRICT (ÉLECTRICITÉ GÉNÉRALE) ===");

        // 1. Récupération ou création sécurisée de la Catégorie Principale unique
        $electricite = Category::firstOrCreate(
            ['nom' => 'Électricité Générale'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // 2. Initialisation rigoureuse des 6 sous-catégories associées à Électricité Générale
        $nomsSousCategories = [
            'Matériel HT (Haute Tension) / MT (Moyenne Tension)',
            'Matériel BT (Basse Tension)',
            'Câbles et Conducteurs',
            'Transformateurs',
            'Appareillage et Accessoires Domestiques / Tertiaires',
            'Accessoires de Pose, Fixation et Raccordement',
        ];

        $sousCategoriesByName = [];
        foreach ($nomsSousCategories as $nomSub) {
            $sub = SousCategorie::firstOrCreate(
                [
                    'category_id' => $electricite->id,
                    'nom' => $nomSub
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
            $sousCategoriesByName[$nomSub] = $sub;
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ])->get($startUrl);

        if ($response->failed()) {
            $this->error("Impossible d'accéder à la page de départ.");
            return;
        }

        $crawler = new Crawler($response->body());

        // Extraction des liens de sous-catégories du menu
        $categoryUrls = [];
        $crawler->filter('li.cat-item a')->each(function (Crawler $a) use (&$categoryUrls) {
            $href = $a->attr('href');
            if ($href && !in_array($href, $categoryUrls)) {
                $categoryUrls[] = $href;
            }
        });

        if (empty($categoryUrls)) {
            $categoryUrls[] = $startUrl;
        }

        $this->info("Total des sections à analyser : " . count($categoryUrls));
        $totalCount = 0;

        foreach ($categoryUrls as $index => $catUrl) {
            try {
                $catResponse = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ])->get($catUrl);

                if ($catResponse->failed()) continue;

                $catCrawler = new Crawler($catResponse->body());

                $productLinks = [];
                $catCrawler->filter('.product-small a.woocommerce-LoopProduct-link, .product a, .box-image a, ul.products li a')->each(function (Crawler $a) use (&$productLinks) {
                    $href = $a->attr('href');
                    if ($href && str_contains($href, '/produit/') && !in_array($href, $productLinks)) {
                        $productLinks[] = $href;
                    }
                });

                if (empty($productLinks)) continue;

                $this->info("\n--- Section [" . ($index + 1) . "/" . count($categoryUrls) . "] (" . count($productLinks) . " produits trouvés) ---");

                foreach ($productLinks as $prodUrl) {
                    $prodResponse = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->get($prodUrl);

                    if ($prodResponse->failed()) continue;

                    $pCrawler = new Crawler($prodResponse->body());

                    $titleNode = $pCrawler->filter('h1');
                    if ($titleNode->count() === 0) continue;
                    $productName = trim($titleNode->text());

                    $descNode = $pCrawler->filter('#tab-description, .woocommerce-product-details__short-description, .product-short-description, .description');
                    $description = $descNode->count() ? trim($descNode->text()) : '';

                    // Classification minutieuse restreinte aux 6 sous-catégories d'Électricité Générale
                    [$sousCatId, $matchedRule] = $this->strictClassify($productName, $description, $catUrl, $sousCategoriesByName);

                    if (!$sousCatId) continue;

                    $sousCatObj = SousCategorie::find($sousCatId);
                    $sousCatName = $sousCatObj ? $sousCatObj->nom : 'Inconnue';

                    $this->line("   ➔ Produit : <fg=cyan>{$productName}</>");
                    $this->line("      Rangé dans : <fg=green;options=bold>[Électricité Générale ➔ {$sousCatName}]</> <fg=gray>(Règle: {$matchedRule})</>");

                    // Extraction robuste de l'image
                    $imageUrl = null;
                    $imgNode = $pCrawler->filter('.woocommerce-product-gallery__image img, .wp-post-image, .product-images img, div.images img');
                    if ($imgNode->count() > 0) {
                        foreach (['data-large_image', 'data-src', 'data-lazy-src', 'src'] as $attr) {
                            $val = $imgNode->first()->attr($attr);
                            if ($val && !str_starts_with($val, 'data:image') && !str_contains($val, 'placeholder')) {
                                $imageUrl = $val;
                                break;
                            }
                        }
                    }
                    $localImagePath = $this->downloadImage($imageUrl, $productName);

                    Product::updateOrCreate(
                        ['name' => $productName],
                        [
                            'sous_categorie_id' => $sousCatId,
                            'description' => $description,
                            'photo' => $localImagePath,
                            'price' => null,
                            'stock' => true,
                        ]
                    );

                    $totalCount++;
                    usleep(100000);
                }

            } catch (\Exception $e) {
                // Ignore les erreurs isolées
            }
        }

        $this->info("\n=== FIN DU SCRAPING STRICT ===");
        $this->info("Total de produits rangés avec succès : " . $totalCount);
    }

    private function strictClassify($name, $desc, $catUrl, $sousCategoriesByName)
    {
        $text = mb_strtolower($name . ' ' . $desc);
        $urlLower = mb_strtolower($catUrl);

        // 1. Matériel HT / MT
        if (preg_match('/cellule|sectionneur|haute tension|moyenne tension|isolateur|parafoudre ht|poste de transformation|interrupteur de charge/i', $text) || str_contains($urlLower, 'ht-mt')) {
            return [$sousCategoriesByName['Matériel HT (Haute Tension) / MT (Moyenne Tension)']->id, 'Matériel HT/MT'];
        }

        // 2. Transformateurs
        if (preg_match('/transformateur|transfo|\btc\b/i', $text) && !str_contains($text, 'compteur') && !str_contains($text, 'kcv')) {
            return [$sousCategoriesByName['Transformateurs']->id, 'Transformateur'];
        }

        // 3. Câbles et Conducteurs
        if (preg_match('/cable|conducteur|u1000|h07v|torsade|cuivre nu|fil electrique/i', $text) || str_contains($urlLower, 'cable')) {
            return [$sousCategoriesByName['Câbles et Conducteurs']->id, 'Câbles & Conducteurs'];
        }

        // 4. Accessoires de Pose, Fixation et Raccordement
        if (preg_match('/\b(pince|cosse|embout|collier|gaine|raccord|manchon|support de barre|boite de derivation|ruban adhesif|chemins de cable|visiere|embout de cablage)\b/i', $text)) {
            return [$sousCategoriesByName['Accessoires de Pose, Fixation et Raccordement']->id, 'Accessoires / Pose'];
        }

        // 5. Appareillage et Accessoires Domestiques / Tertiaires (Prises, Interrupteurs, Éclairage, Luminaires)
        if (preg_match('/prise de courant|interrupteur|va-et-vient|douille|boite d\'encastrement|domotique|knx|hager|lustre|guirlande|projecteur led|lampe|applique|plafonnier|spot|hublot|ruban led/i', $text) || str_contains($urlLower, 'eclairage') || str_contains($urlLower, 'luminaire')) {
            return [$sousCategoriesByName['Appareillage et Accessoires Domestiques / Tertiaires']->id, 'Appareillage & Éclairage'];
        }

        // 6. Matériel BT (Basse Tension) - Fourre-tout par défaut sécurisé (Disjoncteurs, Armoires, Relais, Tableaux, Compteurs)
        return [$sousCategoriesByName['Matériel BT (Basse Tension)']->id, 'Matériel BT (Défaut sélectif)'];
    }

    private function downloadImage($url, $title)
    {
        if (!$url) return null;
        try {
            if (str_starts_with($url, '//')) $url = 'https:' . $url;
            $body = Http::timeout(10)->get($url)->body();
            if (!empty($body)) {
                $filename = Str::slug($title) . '-' . time() . '.jpg';
                Storage::disk('public')->put('products/' . $filename, $body);
                return 'storage/products/' . $filename;
            }
        } catch (\Exception $e) {}
        return null;
    }
}
