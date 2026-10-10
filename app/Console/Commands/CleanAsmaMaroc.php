<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\SousCategorie;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CleanAsmaMaroc extends Command
{
    protected $signature = 'asmamaroc:clean';
    protected $description = 'Vide toutes les tables des produits, sous-catégories et catégories liés au scraping Asma Maroc';

    public function handle()
    {
        if (!$this->confirm('Voulez-vous vraiment supprimer toutes les catégories, sous-catégories et produits de la base de données ?')) {
            $this->info('Opération annulée.');
            return;
        }

        $this->info('Suppression en cours...');

        // Désactivation temporaire des contraintes de clés étrangères pour éviter les erreurs
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Product::truncate();
        SousCategorie::truncate();
        Category::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Base de données nettoyée avec succès ! Vous pouvez relancer le script de scraping propre.');
    }
}