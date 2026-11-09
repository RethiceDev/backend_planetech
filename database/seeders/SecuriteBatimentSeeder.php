<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SousCategorie;

class SecuriteBatimentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer ou récupérer la catégorie principale
        $categorie = Category::firstOrCreate([
            'nom' => 'Sécurité Bâtiment'
        ]);

        // 2. Liste des sous-catégories associées
        $sousCategories = [
            'Caméras de Surveillance (CCTV)',
            'Kits Vidéosurveillance Solaires',
            'Contrôle d\'Accès & Badges',
            'Interphones & Visiophones',
            'Alarmes Anti-intrusion',
            'Détecteurs de Fumée & Incendie',
            'Onduleurs & Sécurité Électrique',
            'Portails Automatiques & Barrières'
        ];

        // 3. Insertion des sous-catégories liées
        foreach ($sousCategories as $nomSousCat) {
            SousCategorie::firstOrCreate([
                'nom' => $nomSousCat,
                'category_id' => $categorie->id
            ]);
        }
    }
}
