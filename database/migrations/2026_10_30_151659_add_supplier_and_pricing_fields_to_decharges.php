<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decharges', function (Blueprint $table) {
            $table->string('fournisseur_nom')->nullable();
            $table->string('fournisseur_entreprise')->nullable();
            $table->string('fournisseur_numero', 50)->nullable();
            $table->string('fournisseur_email')->nullable();
        });

        Schema::table('decharge_items', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 12, 2)->nullable();
            $table->decimal('prix_global', 12, 2)->nullable();
        });

        // La quantité devient facultative.
        // Les anciennes quantités sont conservées.
        DB::statement(
            'ALTER TABLE decharge_items MODIFY quantite INT NULL'
        );
    }

    public function down(): void
    {
        Schema::table('decharge_items', function (Blueprint $table) {
            $table->dropColumn([
                'prix_unitaire',
                'prix_global',
            ]);
        });

        // Retour à la structure initiale :
        // les anciennes lignes doivent avoir une quantité.
        DB::table('decharge_items')
            ->whereNull('quantite')
            ->update(['quantite' => 1]);

        DB::statement(
            'ALTER TABLE decharge_items MODIFY quantite INT NOT NULL'
        );

        Schema::table('decharges', function (Blueprint $table) {
            $table->dropColumn([
                'fournisseur_nom',
                'fournisseur_entreprise',
                'fournisseur_numero',
                'fournisseur_email',
            ]);
        });
    }
};
