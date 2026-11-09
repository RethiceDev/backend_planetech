<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SousCategorie;

class ElectriciteGeneraleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Récupération ou création sécurisée de la Catégorie Principale
        $electricite = Category::firstOrCreate(
            ['nom' => 'Électricité Générale'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // 2. Gestion sécurisée des Sous-catégories associées
        $sousCategories = [
            'Matériel HT (Haute Tension) / MT (Moyenne Tension)',
            'Matériel BT (Basse Tension)',
            'Câbles et Conducteurs',
            'Transformateurs',
            'Appareillage et Accessoires Domestiques / Tertiaires',
            'Accessoires de Pose, Fixation et Raccordement',
        ];

        foreach ($sousCategories as $nomSc) {
            SousCategorie::firstOrCreate(
                [
                    'category_id' => $electricite->id,
                    'nom' => $nomSc
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
