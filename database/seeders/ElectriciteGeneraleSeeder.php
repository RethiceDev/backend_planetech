<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SousCategorie;

class ElectriciteGeneraleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Création de la Catégorie Principale
        $electricite = Category::create([
            'nom' => 'Électricité Générale'
        ]); 

        // 2. Création des 6 Sous-catégories associées
        $sousCategories = [
            [
                'category_id' => $electricite->id,
                'nom' => 'Matériel HT (Haute Tension) / MT (Moyenne Tension)'
            ],
            [
                'category_id' => $electricite->id,
                'nom' => 'Matériel BT (Basse Tension)'
            ],
            [
                'category_id' => $electricite->id,
                'nom' => 'Câbles et Conducteurs'
            ],
            [
                'category_id' => $electricite->id,
                'nom' => 'Transformateurs'
            ],
            [
                'category_id' => $electricite->id,
                'nom' => 'Appareillage et Accessoires Domestiques / Tertiaires'
            ],
            [
                'category_id' => $electricite->id,
                'nom' => 'Accessoires de Pose, Fixation et Raccordement'
            ],
        ];

        foreach ($sousCategories as $sc) {
            SousCategorie::create($sc);
        }
    }
}
