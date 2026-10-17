<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('nom_donnateur')->default('Armelle ASSOGBA'); // Directeur Général par défaut
            $table->string('role_donnateur')->default('Directeur Général');
            $table->string('nom_procurant');
            $table->string('cip_procurant'); // Strictement 10 chiffres
            $table->date('expiration_cip');
            $table->string('role_procurant');
            $table->string('email_procurant');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurations');
    }
};
