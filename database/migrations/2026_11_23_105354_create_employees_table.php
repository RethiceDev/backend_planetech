<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Obligatoires
            $table->string('name', 100);
            $table->string('prenoms', 150);
            $table->string('email', 150)->unique();
            $table->string('telephone', 10);
            $table->string('service', 100);
            $table->string('poste', 100);
            $table->date('date_embauche');
            $table->string('mac_address', 17)->unique();

            // Facultatifs
            $table->string('mac_address_2', 17)->nullable()->unique();
            $table->string('photo')->nullable();
            $table->string('type_contrat', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};