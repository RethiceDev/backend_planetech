<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisations', function (Blueprint $table) {
            $table->id();

            $table->string('domaine', 150);
            $table->string('titre', 255);
            $table->text('description');

            // Chemin du fichier dans storage/app/public
            $table->string('image');

            // Permet de choisir l'ordre d'affichage
            $table->unsignedInteger('ordre')->default(0);

            // Permet de masquer une réalisation sans la supprimer
            $table->boolean('actif')->default(true);

            $table->timestamps();

            $table->index('domaine');
            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisations');
    }
};
