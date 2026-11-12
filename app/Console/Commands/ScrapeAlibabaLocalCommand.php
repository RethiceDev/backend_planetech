<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\SousCategorie;
use App\Models\Product;

class ScrapeAlibabaLocalCommand extends Command
{
    protected $signature = 'scrape:alibaba-local';
    protected $description = 'Importe les régulateurs depuis le fichier local alibaba.html';

    public function handle()
    {
        $this->info("Début de l'importation Alibaba depuis le fichier local HTML...");

        $electricite = Category::firstOrCreate(['nom' => 'Électricité Générale']);
        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $electricite->id,
            'nom' => 'Matériel BT (Basse Tension)'
        ]);

        $filePath = storage_path('app/alibaba.html');
        if (!file_exists($filePath)) {
            $this->error("Le fichier storage/app/alibaba.html est introuvable !");
            return;
        }

        $html = file_get_contents($filePath);

        // Repérage de la variable dans le texte brut
        $pos = strpos($html, 'window.__page_data');
        if ($pos === false) {
            $pos = strpos($html, '__page_data');
        }

        if ($pos === false) {
            $this->error("Impossible de localiser window.__page_data dans le fichier.");
            return;
        }

        // Extraction précise via le compteur d'accolades imbriquées
        $startBrace = strpos($html, '{', $pos);
        if ($startBrace === false) {
            $this->error("Accolade ouvrante introuvable après __page_data.");
            return;
        }

        $depth = 0;
        $jsonString = '';
        $len = strlen($html);

        for ($i = $startBrace; $i < $len; $i++) {
            $char = $html[$i];
            $jsonString .= $char;
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }

        $data = json_decode($jsonString, true);
        if (!$data) {
            $this->error("Erreur lors du décodage du JSON. Code d'erreur : " . json_last_error());
            return;
        }

        $productsList = [];
        $this->extractProductsRecursive($data, $productsList);

        $this->info(" -> Produits détectés dans le JSON : " . count($productsList));

        if (empty($productsList)) {
            $this->warn("Aucun produit exploitable trouvé dans la structure JSON.");
            return;
        }

        foreach ($productsList as $item) {
            try {
                $name = $item['subject'] ?? $item['title'] ?? $item['productName'] ?? null;
                if (empty($name)) continue;

                $priceInfo = $item['price'] ?? $item['minPrice'] ?? 'Sur devis';
                $description = "Prix indicatif : " . (is_array($priceInfo) ? json_encode($priceInfo) : $priceInfo);

                Product::firstOrCreate(
                    [
                        'sous_categorie_id' => $sousCategorie->id,
                        'name' => trim($name)
                    ],
                    [
                        'price' => null,
                        'photo' => null,
                        'description' => trim($description),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                $this->line("    -> Importé avec succès : " . trim($name));
            } catch (\Exception $e) {}
        }

        $this->info("Importation terminée !");
    }

    private function extractProductsRecursive($array, &$results)
    {
        if (!is_array($array)) return;

        if (isset($array['subject']) || (isset($array['title']) && (isset($array['price']) || isset($array['offerId'])))) {
            $results[] = $array;
        }

        foreach ($array as $value) {
            if (is_array($value)) {
                $this->extractProductsRecursive($value, $results);
            }
        }
    }
}