<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45);
            $table->string('page', 191);
            $table->string('source', 30)->default('direct');
            $table->string('referrer', 500)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->date('visited_on');
            $table->timestamps();

            // Une même IP ne compte qu'une fois par jour et par page
            $table->unique(['ip', 'page', 'visited_on'], 'visits_unique_per_day');
            $table->index('visited_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
