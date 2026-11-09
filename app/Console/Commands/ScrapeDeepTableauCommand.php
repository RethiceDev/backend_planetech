<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use App\Models\SousCategorie;
use Illuminate\Support\Str;

class ScrapeDeepTableauCommand extends Command
{
    protected $signature = 'scrape:deep-tableau {url}';
    protected $description = 'Scrape en profondeur avec sélecteurs élargis pour capturer les titres et classer les produits';

    public function handle()
    {
        $startUrl = $this->argument('url');
        $this->info("=== DÉBUT DU SCRAPING ET RANGEMENT INTELLIGENT ===");

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ])->get($startUrl);

        if ($response->failed()) {
            $this->error("Impossible d'accéder à la page de départ.");
            return;
        }

        $crawler = new Crawler($response->body());
        $sousCategoriesByName = SousCategorie::all()->keyBy('nom');
        $sousCategoriesById = SousCategorie::all()->keyBy('id');

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

        $this->info("Total des sections/sous-catégories à analyser : " . count($categoryUrls));
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

                    // Sélecteur élargi à tous les H1 de la page produit
                    $titleNode = $pCrawler->filter('h1');
                    if ($titleNode->count() === 0) {
                        continue;
                    }
                    $productName = trim($titleNode->text());

                    $descNode = $pCrawler->filter('#tab-description, .woocommerce-product-details__short-description, .product-short-description, .description');
                    $description = $descNode->count() ? trim($descNode->text()) : '';

                    // Classification intelligente avec transmission de l'URL source
                    [$sousCatId, $matchedRule] = $this->smartClassify($productName, $description, $catUrl, $sousCategoriesByName);

                    if (!$sousCatId) {
                        continue;
                    }

                    $sousCatName = $sousCategoriesById->get($sousCatId)->nom ?? 'Inconnue';

                    // AFFICHAGE EXPLICITE DANS LE TERMINAL
                    $this->line("   ➔ Produit : <fg=cyan>{$productName}</>");
                    $this->line("      Rangé dans : <fg=green;options=bold>[{$sousCatName}]</> <fg=gray>(Règle: {$matchedRule})</>");

                    // Image HD
                    $imageUrl = null;
                    $imgNode = $pCrawler->filter('.woocommerce-product-gallery__image img, .product-images img, .attachment-shop_single img');
                    if ($imgNode->count() > 0) {
                        $imageUrl = $imgNode->attr('data-large_image') ?? $imgNode->attr('data-src') ?? $imgNode->attr('src');
                    }
                    $localImagePath = $this->downloadImage($imageUrl, $productName);

                    // Enregistrement en BDD
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

        $this->info("\n=== FIN DU SCRAPING INTELLIGENT ===");
        $this->info("Total de produits rangés avec succès : " . $totalCount);
    }

    private function smartClassify($name, $desc, $catUrl, $sousCategoriesByName)
    {
        $text = mb_strtolower($name . ' ' . $desc);
        $urlLower = mb_strtolower($catUrl);

        // 1. HERITAGE DIRECT DE LA CATEGORIE SOURCE (Le moyen le plus infaillible)
        if (str_contains($urlLower, 'eclairage') || str_contains($urlLower, 'luminaire') || str_contains($urlLower, 'projecteur')) {
            return [$sousCategoriesByName->get('Appareillage et Accessoires Domestiques / Tertiaires')?->id, 'Catégorie Source: Éclairage'];
        }
        if (str_contains($urlLower, 'cable') || str_contains($urlLower, 'conducteur')) {
            return [$sousCategoriesByName->get('Câbles et Conducteurs')?->id, 'Catégorie Source: Câbles'];
        }
        if (str_contains($urlLower, 'transformateur')) {
            return [$sousCategoriesByName->get('Transformateurs')?->id, 'Catégorie Source: Transformateurs'];
        }
        if (str_contains($urlLower, 'telecom') || str_contains($urlLower, 'fibre') || str_contains($urlLower, 'reseau')) {
            $targetCat = $sousCategoriesByName->get('Réseaux & Télécoms') ?? $sousCategoriesByName->get('Matériel BT (Basse Tension)');
            return [$targetCat?->id, 'Catégorie Source: Télécoms & Réseaux'];
        }

        // 2. REGLES PRIORITAIRES PAR MOTS-CLES (Luminaires, Lustres, Guirlandes, Bandes LED)
        if (preg_match('/lustre|guirlande|bande lumineuse|projecteur\s+led|lampe|applique|plafonnier|spot|hublot|ruban led/i', $text)) {
            return [$sousCategoriesByName->get('Appareillage et Accessoires Domestiques / Tertiaires')?->id, 'Règle: Éclairage & Luminaires'];
        }

        // 3. Matériel Télécoms & Réseaux (Fibre optique, SFP, switch, routeur, jarretière, etc.)
        if (preg_match('/fibre optique|ftth|sfp|transceiver|patchcord|jarretiere optique|coffret optique|roe|pto|telecom|switch réseau|routeur/i', $text)) {
            $targetCat = $sousCategoriesByName->get('Réseaux & Télécoms') ?? $sousCategoriesByName->get('Matériel BT (Basse Tension)');
            return [$targetCat?->id, 'Règle: Télécoms & Réseaux'];
        }

        // 4. Accessoires de Pose, Fixation et Raccordement (Strictement délimités)
        if (preg_match('/\b(pince|cosse|embout|collier|gaine|raccord|manchon|support de barre|boite de derivation|ruban adhesif|chemins de cable|visiere)\b/i', $text) && !str_contains($text, 'projecteur')) {
            return [$sousCategoriesByName->get('Accessoires de Pose, Fixation et Raccordement')?->id, 'Règle: Accessoires / Pose'];
        }

        // 5. Transformateurs (Sécurisé pour exclure les kits vidéo KCV, KC, et compteurs)
        if (preg_match('/transformateur|transfo|\btc\b/i', $text) && !str_contains($text, 'compteur') && !str_contains($text, 'kcv') && !str_contains($text, 'kc-')) {
            return [$sousCategoriesByName->get('Transformateurs')?->id, 'Règle: Transformateur'];
        }

        // 6. Matériel HT / MT
        if (preg_match('/cellule|sectionneur|haute tension|moyenne tension|isolateur|parafoudre ht/i', $text)) {
            return [$sousCategoriesByName->get('Matériel HT (Haute Tension) / MT (Moyenne Tension)')?->id, 'Règle: Matériel HT/MT'];
        }

        // 7. Câbles et Conducteurs
        if (preg_match('/cable|conducteur|u1000|h07v|torsade|cuivre nu/i', $text)) {
            return [$sousCategoriesByName->get('Câbles et Conducteurs')?->id, 'Règle: Câbles'];
        }

        // 8. Appareillage Domestique / Tertiaire (Prises, Interrupteurs, KNX, Domotique)
        if (preg_match('/prise de courant|interrupteur simple|va-et-vient|douille|boite d\'encastrement|domotique|knx|hager/i', $text)) {
            return [$sousCategoriesByName->get('Appareillage et Accessoires Domestiques / Tertiaires')?->id, 'Règle: Appareillage Domestique'];
        }

        // 9. Matériel BT (Disjoncteurs, Armoires, Relais, Vidéophonie KCV/KC, Compteurs, Multimètres)
        if (preg_match('/disjoncteur|differentiel|contacteur|tableau|coffret|armoire|boitier|fusible|relais|variateur|demarreur|prise|fiche|multimetre|compteur|voltmetre|amperemetre|parafoudre|inverseur|horloge|minuterie|kit video|moniteur video|platine de rue|combine|badge/i', $text)) {
            return [$sousCategoriesByName->get('Matériel BT (Basse Tension)')?->id, 'Règle: Matériel BT / Vidéophonie / Appareillage'];
        }

        // Sécurité par défaut (Matériel BT)
        return [$sousCategoriesByName->get('Matériel BT (Basse Tension)')?->id, 'Règle: Défaut BT'];
    }

    private function downloadImage($url, $title)
    {
        if (!$url) return null;
        try {
            if (str_starts_with($url, '//')) $url = 'https:' . $url;
            $body = Http::timeout(10)->get($url)->body();
            if (!empty($body)) {
                $name = 'produits/' . Str::slug($title) . '-' . time() . '.jpg';
                Storage::disk('public')->put($name, $body);
                return $name;
            }
        } catch (\Exception $e) {}
        return null;
    }
}
