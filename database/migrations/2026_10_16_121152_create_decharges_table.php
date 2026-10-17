<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decharges', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // ex: DEC-202610-001

            // Informations bénéficiaire / partie prenante
            $table->string('nom');
            $table->string('role');
            $table->string('entreprise')->nullable();
            $table->string('numero');
            $table->string('email');

            // Type de décharge
            $table->enum('type', ['argent', 'materiel', 'responsabilite']);

            // Champs spécifiques selon le type
            $table->decimal('somme', 12, 2)->nullable();
            $table->string('motif')->nullable();
            $table->text('description')->nullable();

            // Signature numérique (base64 PNG)
            $table->longText('signature_data');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decharges');
    }
};
