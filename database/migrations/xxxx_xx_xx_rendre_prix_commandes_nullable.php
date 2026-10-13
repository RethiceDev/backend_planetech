<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->decimal('total', 14, 2)->nullable()->default(null)->change();
        });

        Schema::table('commande_items', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 14, 2)->nullable()->default(null)->change();
            $table->decimal('total_ligne', 14, 2)->nullable()->default(null)->change();
            $table->boolean('disponible')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->decimal('total', 14, 2)->default(0)->nullable(false)->change();
        });

        Schema::table('commande_items', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 14, 2)->default(0)->nullable(false)->change();
            $table->decimal('total_ligne', 14, 2)->default(0)->nullable(false)->change();
        });
    }
};
