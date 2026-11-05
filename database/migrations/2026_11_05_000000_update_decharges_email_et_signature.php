<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * E-mails facultatifs + signature numérique abandonnée (signature à la main).
     *
     * - On ne modifie PAS les anciennes migrations (déjà exécutées) : on ajoute celle-ci.
     * - signature_data est conservée (nullable) pour garder les signatures des anciennes décharges.
     */
    public function up(): void
    {
        Schema::table('decharges', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->longText('signature_data')->nullable()->change();
        });

        // Colonne ajoutée par une autre migration (fournisseur / livreur) : on vérifie qu'elle existe.
        if (Schema::hasColumn('decharges', 'fournisseur_email')) {
            Schema::table('decharges', function (Blueprint $table) {
                $table->string('fournisseur_email')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Retour arrière volontairement vide : remettre ces colonnes en
        // "non nullable" échouerait dès qu'une ligne contient une valeur vide.
    }
};
