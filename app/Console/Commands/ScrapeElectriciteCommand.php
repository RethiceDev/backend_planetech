<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use App\Models\SousCategorie;
use Illuminate\Support\Str;

class ScrapeElectriciteCommand extends Command
{
    protected $signature = 'scrape:electricite {url}';
    protected $description = 'Scrape, normalise, classe les produits et télécharge leurs images';

    public function handle()
    {
        $url = $this->argument('url');
        $this->info("Début du scraping sur l'URL : " . $url);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ])->get($url);

        if ($response->failed()) {
            $this->error("Erreur lors de la récupération de la page.");
            return;
        }

        $crawler = new Crawler($response->body());
        $sousCategories = SousCategorie::all()->keyBy('nom');

        $selector = '.product-small, .product, li.product';
        if ($crawler->filter($selector)->count() === 0) {
            $selector = '.col-inner, .box-text';
        }

        $count = 0;

        $crawler->filter($selector)->each(function (Crawler $node) use ($sousCategories, $url, &$count) {

            $nameNode = $node->filter('.name a, .woocommerce-loop-product__title, h2, h3');
            if ($nameNode->count() === 0) return;
            $name = trim($nameNode->text());

            $descNode = $node->filter('.short-description, .box-text p, .description');
            $description = $descNode->count() ? trim($descNode->text()) : '';

            $imgNode = $node->filter('img');
            $imageUrl = null;
            if ($imgNode->count() > 0) {
                $imageUrl = $imgNode->attr('data-srcset')
                    ?? $imgNode->attr('data-src')
                    ?? $imgNode->attr('data-large_image')
                    ?? $imgNode->attr('src');

                if ($imageUrl && str_contains($imageUrl, ',')) {
                    $imageUrl = trim(explode(',', $imageUrl)[0]);
                    $imageUrl = explode(' ', $imageUrl)[0];
                }
            }

            if (empty($name)) return;

            $rawText = mb_strtolower($name . ' ' . $description);

            // Normalisation des abréviations
            $abbreviationsMap = [
                '/\bdicj\b|\bdisj\.?\b/i' => 'disjoncteur',
                '/\btrfo\b|\btransfo\.?\b/i' => 'transformateur',
                '/\bcbl\b|\bcabl\.?\b/i' => 'câble',
                '/\binter\.?\b/i' => 'interrupteur',
                '/\bonds?\b/i' => 'onduleur',
                '/\bdiff\.?\b/i' => 'differentiel',
                '/\bparaf\.?\b/i' => 'parafoudre',
            ];

            $normalizedText = preg_replace(array_keys($abbreviationsMap), array_values($abbreviationsMap), $rawText);

            // Classification intelligente
            $sousCatId = null;
            if (str_contains($url, 'materiel-de-ligne')) {
                if (preg_match('/pince|manchon|console|connecteur|berceau|accessoire de chaine|bloc de doublement|bloc de terre|disjoncteur haut de poteau/i', $normalizedText)) {
                    $sousCatId = $sousCategories->get('Accessoires de Pose, Fixation et Raccordement')?->id;
                } elseif (preg_match('/parafoudre|isolateur|interrupteur aerien/i', $normalizedText)) {
                    $sousCatId = $sousCategories->get('Matériel HT (Haute Tension) / MT (Moyenne Tension)')?->id;
                }
            }

            if (!$sousCatId) {
                if (preg_match('/cellule|sectionneur|parafoudre|haute tension|moyenne tension|isolateur/i', $normalizedText)) {
                    $sousCatId = $sousCategories->get('Matériel HT (Haute Tension) / MT (Moyenne Tension)')?->id;
                } elseif (preg_match('/transformateur|\btc\b/i', $normalizedText)) {
                    $sousCatId = $sousCategories->get('Transformateurs')?->id;
                } elseif (preg_match('/cable|conducteur|u1000|h07v|torsade|cuivre nu/i', $normalizedText)) {
                    $sousCatId = $sousCategories->get('Câbles et Conducteurs')?->id;
                } elseif (preg_match('/onduleur|disjoncteur|differentiel|contacteur|tableau de distribution|coffret|groupe electrogene/i', $normalizedText)) {
                    $sousCatId = $sousCategories->get('Matériel BT (Basse Tension)')?->id;
                } elseif (preg_match('/prise|interrupteur|douille|boite d\'encastrement|domotique/i', $normalizedText)) {
                    $sousCatId = $sousCategories->get('Appareillage et Accessoires Domestiques / Tertiaires')?->id;
                } elseif (preg_match('/pince|cosse|embout|collier|gaine|chemin de cable|ruban adhesif|boite de derivation/i', $normalizedText)) {
                    $sousCatId = $sousCategories->get('Accessoires de Pose, Fixation et Raccordement')?->id;
                }
            }

            if (!$sousCatId) return;

            // TÉLÉCHARGEMENT LOCAL DE L'IMAGE
            $localImagePath = null;
            if ($imageUrl) {
                try {
                    // Nettoyage de l'URL si elle commence par //
                    if (str_starts_with($imageUrl, '//')) {
                        $imageUrl = 'https:' . $imageUrl;
                    }

                    $imageContents = Http::timeout(10)->get($imageUrl)->body();
                    if (!empty($imageContents)) {
                        $imageName = 'produits/' . Str::slug($name) . '-' . time() . '.jpg';
                        Storage::disk('public')->put($imageName, $imageContents);
                        $localImagePath = $imageName; // ex: produits/berceauxmax-171...jpg
                    }
                } catch (\Exception $e) {
                    // En cas d'échec de téléchargement, on laisse à null
                }
            }

            // Enregistrement en base de données
            Product::updateOrCreate(
                ['name' => $name],
                [
                    'sous_categorie_id' => $sousCatId,
                    'description' => $description,
                    'photo' => $localImagePath,
                    'price' => null,
                    'stock' => true,
                ]
            );

            $this->info("[OK] Enregistré avec image locale -> " . $name);
            $count++;
        });

        $this->info("Scraping et téléchargement terminés ! Total : " . $count);
    }
}
