<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\SousCategorie;
use App\Models\Product;

class ScrapeAlibabaRegulateursCommand extends Command
{
    protected $signature = 'scrape:regulateurs-alibaba';
    protected $description = 'Importe les régulateurs de tension depuis le fichier HTML local d\'Alibaba';

    public function handle()
    {
        $this->info("Début de l'importation Alibaba depuis le fichier local...");

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

        if (!preg_match('/window\.__page_data\s*=\s*(\{.+?\});\s*(<\/script>|\n)/s', $html, $matches)) {
            preg_match('/window\.__page_data\s*=\s*(\{.+?\});/s', $html, $matches);
        }

        if (empty($matches[1])) {
            $this->error("Impossible d'extraire l'objet window.__page_data du fichier HTML.");
            return;
        }

        $data = json_decode($matches[1], true);
        if (!$data) {
            $this->error("Erreur lors du décodage du JSON Alibaba.");
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

        $this->info("Importation des régulateurs Alibaba terminée avec succès !");
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