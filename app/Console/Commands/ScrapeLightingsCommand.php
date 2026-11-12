<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\SousCategorie;
use App\Models\Product;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScrapeLightingsCommand extends Command
{
    protected $signature = 'scrape:lightings';
    protected $description = 'Importe les luminaires depuis le fichier MHTML local';

    public function handle()
    {
        $this->info("Début de l'importation depuis le fichier MHTML...");

        // 1. Gestion de la catégorie et sous-catégorie
        $electricite = Category::firstOrCreate(['nom' => 'Électricité Générale']);
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $electricite->id,
            'nom' => 'Appareillage et Accessoires Domestiques / Tertiaires'
        ]);

        // 2. Chargement du fichier MHTML local
        $filePath = storage_path('app/lightings.html');
        if (!file_exists($filePath)) {
            $this->error("Le fichier storage/app/lightings.mhtml est introuvable !");
            return;
        }

        $content = file_get_contents($filePath);

        // Extraction de la partie HTML à l'intérieur du conteneur MHTML (recherche du début du balisage HTML)
        if (preg_match('/<html.*?>/is', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $html = substr($content, $matches[0][1]);
        } else {
            $html = $content; // Fallback si le format brut convient
        }

        $crawler = new Crawler($html);

        // 3. Ciblage des produits dans le contenu extrait
        $products = $crawler->filter('.search-list-container .product-item, div.dir-product-wrap, div.product-info-wrap, div[data-index]');

        if ($products->count() === 0) {
            // Sélecteur de secours élargi si le conteneur MHTML encapsule différemment
            $products = $crawler->filter('div.item, div.product-item, li.product');
        }

        if ($products->count() === 0) {
            $this->warn("Aucun produit trouvé dans le fichier MHTML avec les sélecteurs actuels.");
            return;
        }

        $products->each(function (Crawler $node) use ($sousCategorie) {
            try {
                $nomNode = $node->filter('.product-name a, h2 a, h3 a, a.J-grid-track-link, .search-prd-title a');
                if ($nomNode->count() === 0) return;
                $name = trim($nomNode->text());
                if (empty($name)) return;

                $descNode = $node->filter('.search-prd-brief, .product-desc, .price, p');
                $description = $descNode->count() > 0 ? trim($descNode->text()) : "Luminaire / Éclairage disponible sur devis.";

                Product::firstOrCreate(
                    ['sous_categorie_id' => $sousCategorie->id, 'name' => $name],
                    [
                        'price' => null,
                        'photo' => null,
                        'description' => $description,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                $this->line("    -> Importé : {$name}");
            } catch (\Exception $e) {}
        });

        $this->info("Importation MHTML terminée avec succès !");
    }
}
