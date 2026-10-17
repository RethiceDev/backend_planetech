<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procuration_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procuration_id')->constrained('procurations')->onDelete('cascade');
            $table->text('libelle_action');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procuration_actions');
    }
};

