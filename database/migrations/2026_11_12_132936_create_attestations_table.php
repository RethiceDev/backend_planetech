<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attestations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('type', 40);                       // voir Attestation::TYPES
            $table->string('numero', 100)->nullable();        // N° officiel (stages)
            $table->string('societe_emettrice', 100)->default('PLANETECH SARL');
            $table->string('beneficiaire', 150);              // personne ou société
            $table->string('genre', 1)->nullable();           // M / F
            $table->string('poste', 150)->nullable();         // poste ou domaine du stage
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('duree', 60)->nullable();          // ex: deux (02) ans
            $table->string('objet')->nullable();              // travaux réalisés
            $table->string('periode_texte', 100)->nullable(); // ex: Septembre 2017
            $table->date('date_etablissement');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};