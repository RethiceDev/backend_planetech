<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sous_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->string('nom');

            $table->timestamps();

            $table->unique(['category_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sous_categories');
    }
};