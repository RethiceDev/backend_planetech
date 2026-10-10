<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commande_items', function (Blueprint $table) {
            $table->boolean('disponible')
                ->nullable()
                ->after('total_ligne');
        });
    }

    public function down(): void
    {
        Schema::table('commande_items', function (Blueprint $table) {
            $table->dropColumn('disponible');
        });
    }
};