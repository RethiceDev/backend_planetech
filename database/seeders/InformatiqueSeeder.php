<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SousCategorie;

class InformatiqueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Catégorie principale (firstOrCreate pour éviter les doublons)
        $informatique = Category::firstOrCreate([
            'nom' => 'Informatique et Bureautique'
        ]);

        // 2. Liste complète des sous-catégories
        $sousCategories = [
            'Ordinateurs Portables et PC de Bureau',
            'Écrans et Périphériques',
            'Composants Informatiques',
            'Impression et Consommables',
            'Stockage et Sauvegarde',
            'Accessoires et Connectique PC',
            'Logiciels et Licences',
            'Alimentation et Onduleurs',
        ];

        // 3. Insertion en base de données avec association
        foreach ($sousCategories as $nom) {
            SousCategorie::firstOrCreate([
                'category_id' => $informatique->id,
                'nom'         => $nom,
            ]);
        }
    }
}
