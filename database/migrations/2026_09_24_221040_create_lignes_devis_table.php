<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_devis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('devis_id')
                ->constrained('devis')
                ->cascadeOnDelete();

            $table->string('designation');

            $table->unsignedInteger('quantite');

            $table->decimal('prix_unitaire', 15, 2);

            $table->decimal('total_ligne', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_devis');
    }
};
