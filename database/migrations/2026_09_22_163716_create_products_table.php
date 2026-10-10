<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->foreignId('sous_categorie_id')
                ->constrained('sous_categories')
                ->cascadeOnDelete();

            $table->decimal('price', 12, 2)->nullable();

            $table->text('description');

            $table->string('photo')->nullable();

            $table->boolean('stock')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
