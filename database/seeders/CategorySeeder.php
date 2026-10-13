<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['nom' => 'Électricité', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Informatique', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'BTP', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Télécoms & Réseau', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Caméra de surveillance & Sécurité', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Froid et Climatisation', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('categories')->insert($categories);
    }
}