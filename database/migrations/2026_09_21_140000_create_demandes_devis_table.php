<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_devis', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();

            $table->string('type_client')->nullable();

            $table->string('raison_sociale')->nullable();

            $table->string('nom');

            $table->string('email');

            $table->string('indicatif')->nullable();

            $table->string('telephone')->nullable();

            $table->string('domaine')->nullable();

            $table->text('besoins');

            $table->string('statut')
                ->default('en_attente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_devis');
    }
};
