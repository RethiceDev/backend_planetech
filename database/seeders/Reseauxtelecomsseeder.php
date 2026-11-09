<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SousCategorie;

class ReseauxTelecomsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Catégorie principale (firstOrCreate : relançable sans doublons)
        $reseaux = Category::firstOrCreate([
            'nom' => 'Réseaux & Télécoms'
        ]);

        // 2. Sous-catégories associées
        $sousCategories = [
            'Câbles Réseau et Télécoms',
            'Fibre Optique',
            'Connectique, Prises et Brassage',
            'Équipements Actifs (Switchs, Routeurs, Wi-Fi)',
            'Baies et Coffrets de Brassage',
            'Téléphonie et Communication',
            'Antennes, TV et Satellite',
            'Vidéosurveillance IP',
            'Outillage et Mesure Réseau',
        ];

        foreach ($sousCategories as $nom) {
            SousCategorie::firstOrCreate([
                'category_id' => $reseaux->id, 
                'nom'         => $nom,
            ]);
        }
    }
}
