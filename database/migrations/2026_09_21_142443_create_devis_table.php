<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('demande_devis_id')
                ->constrained('demandes_devis')
                ->cascadeOnDelete();

            $table->string('numero')->unique();

            $table->date('date_emission');
            $table->date('date_validite');

            $table->decimal('sous_total', 15, 2)->default(0);
            $table->decimal('remise', 15, 2)->default(0);
            $table->decimal('tva', 15, 2)->default(0);
            $table->decimal('total_ttc', 15, 2)->default(0);

            $table->string('fichier_pdf')->nullable();

            $table->text('conditions_paiement')->nullable();

            $table->string('delai_realisation')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
