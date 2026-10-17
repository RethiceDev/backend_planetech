<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decharge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decharge_id')->constrained('decharges')->onDelete('cascade');
            $table->string('designation');
            $table->integer('quantite');
            $table->enum('etat', ['neuf', 'partiellement_utilise', 'vieux', 'defectueux']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decharge_items');
    }
};
