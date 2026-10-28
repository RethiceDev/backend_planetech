<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Category;
use App\Models\SousCategorie;
use App\Models\Product;

class ScrapeMadeInChinaSmart extends Command
{
    protected $signature = 'madeinchina:smart-scrape';
    protected $description = 'Scrape et range intelligemment les produits Made-in-China depuis un fichier HTML local';

    public function handle()
    {
        $this->info('Début de l\'importation intelligente depuis Made-in-China...');

        $filePath = storage_path('app/page.html');

        if (!file_exists($filePath)) {
            $this->error("Le fichier page.html est introuvable dans storage/app/ ! Enregistre la page web dedans.");
            return;
        }

        $html = file_get_contents($filePath);
        $crawler = new Crawler($html);

        // Sélecteurs cibles pour les éléments de produits sur Made-in-China
        $selectors = [
            '.product-item',
            '.sr-sideListing-item',
            '.product-node',
            '.prod-item',
            'div[data-ap="product-item"]',
            '.search-product-item'
        ];

        $productNodes = null;
        foreach ($selectors as $selector) {
            $nodes = $crawler->filter($selector);
            if ($nodes->count() > 0) {
                $productNodes = $nodes;
                $this->info("Sélecteur détecté : {$selector} ({$nodes->count()} produits trouvés)");
                break;
            }
        }

        if (!$productNodes || $productNodes->count() === 0) {
            $this->warn("Aucun bloc produit standard n'a été détecté dans le fichier HTML.");
            return;
        }

        $count = 0;
        $productNodes->each(function (Crawler $node) use (&$count) {
            try {
                // 1. Extraction du nom du produit
                $titleNode = $node->filter('.product-name, .title, h2, h3, .prod-name, a[title]');
                if ($titleNode->count() === 0) return;
                $productName = trim($titleNode->text());

                // 2. Extraction de l'image
                $imgNode = $node->filter('img');
                $imgUrl = null;
                if ($imgNode->count() > 0) {
                    $imgUrl = $imgNode->attr('data-original')
                           ?: $imgNode->attr('data-src')
                           ?: $imgNode->attr('src')
                           ?: $imgNode->attr('data-lazyload-src');
                }

                // RÈGLE STRICTE : AUCUN PRODUIT SANS PHOTO
                if (!$imgUrl) {
                    $this->line("    [IGNORÉ - Pas d'image] {$productName}");
                    return;
                }

                if (str_starts_with($imgUrl, '//')) {
                    $imgUrl = 'https:' . $imgUrl;
                }

                // Téléchargement et stockage local
                $photoPath = null;
                try {
                    $imageContents = @file_get_contents($imgUrl);
                    if ($imageContents) {
                        $imageName = 'products/mic-' . Str::slug(Str::limit($productName, 30, '')) . '-' . time() . '.jpg';
                        Storage::disk('public')->put($imageName, $imageContents);
                        $photoPath = $imageName;
                    }
                } catch (\Exception $ex) {}

                if (!$photoPath) {
                    $this->line("    [IGNORÉ - Échec téléchargement] {$productName}");
                    return;
                }

                // 3. Classification Intelligente (Catégorie & Sous-Catégorie)
                [$mainCategoryName, $sousCatName] = $this->smartCategorize($productName);

                $category = Category::firstOrCreate(['nom' => $mainCategoryName]);

                $sousCat = SousCategorie::firstOrCreate([
                    'category_id' => $category->id,
                    'nom' => $sousCatName
                ]);

                // 4. Enregistrement en base avec prix STRICTEMENT NULL
                Product::firstOrCreate(
                    [
                        'name' => $productName,
                        'sous_categorie_id' => $sousCat->id
                    ],
                    [
                        'description' => "Matériel importé et certifié de haute qualité - Planetech SARL",
                        'photo' => $photoPath,
                        'price' => null, // Prix strictement NULL
                        'stock' => true
                    ]
                );

                $count++;
                $this->line("    [SUCCÈS {$count}] {$productName} -> [{$category->nom} > {$sousCat->nom}]");

            } catch (\Exception $e) {}
        });

        $this->info("Importation intelligente terminée : {$count} produits enregistrés avec succès !");
    }

    /**
     * Système intelligent de classification selon le nom du produit
     */
    private function smartCategorize(string $text): array
    {
        $name = mb_strtolower($text);

        // Moteur Électrique & Composants associés
        if (str_contains($name, 'moteur') || str_contains($name, 'motor') || str_contains($name, 'alternateur')) {
            return ['Électricité', 'Moteur Électrique'];
        }

        // Câbles & Fils Électriques
        if (str_contains($name, 'cable') || str_contains($name, 'câble') || str_contains($name, 'fil') || str_contains($name, 'wire') || str_contains($name, 'conducteur')) {
            return ['Électricité', 'Câblage & Fils'];
        }

        // Énergie Solaire & Onduleurs
        if (str_contains($name, 'solaire') || str_contains($name, 'solar') || str_contains($name, 'panneau') || str_contains($name, 'onduleur') || str_contains($name, 'batterie')) {
            return ['Électricité', 'Énergie Solaire'];
        }

        // Disjoncteurs & Protection
        if (str_contains($name, 'disjoncteur') || str_contains($name, 'fusible') || str_contains($name, 'interrupteur') || str_contains($name, 'protection')) {
            return ['Électricité', 'Appareillage & Protection'];
        }

        // Réseau & Télécoms
        if (str_contains($name, 'routeur') || str_contains($name, 'switch') || str_contains($name, 'fibre') || str_contains($name, 'ethernet') || str_contains($name, 'camera')) {
            return ['Télécoms & Réseau', 'Équipement Réseau'];
        }

        // Par défaut (si aucun mot-clé spécifique n'est détecté)
        return ['Électricité', 'Équipements Électriques Divers'];
    }
}
