<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SousCategorie;

class ClimatisationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer ou récupérer la catégorie principale
        $categorie = Category::firstOrCreate([
            'nom' => 'Froid & Climatisation'
        ]);

        // 2. Liste des sous-catégories associées
        $sousCategories = [
            'Climatiseurs Split',
            'Climatiseurs Armoires',
            'Climatiseurs Cassettes',
            'Chambres Froides (Positives & Négatives)',
            'Compresseurs Frigorifiques',
            'Tubes et Isolants Cuivre',
            'Gaz Réfrigérants (R410A, R32, R134a)', 
            'Pièces Détachées & Accessoires'
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
