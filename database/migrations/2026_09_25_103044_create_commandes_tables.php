<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | COMMANDES
        |--------------------------------------------------------------------------
        */

        Schema::create('commandes', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();

            $table->enum('type_client', [
                'particulier',
                'entreprise',
            ]);

            // Nom du client particulier ou nom du contact
            $table->string('nom');

            // Nom commercial / raison sociale pour une entreprise
            $table->string('raison_sociale')->nullable();

            $table->string('email');

            $table->string('telephone');

            $table->decimal('total', 15, 2)->default(0);

            $table->string('statut')->default('recue');

            $table->timestamps();
        });


        /*
        |--------------------------------------------------------------------------
        | LIGNES DE COMMANDES
        |--------------------------------------------------------------------------
        */

        Schema::create('commande_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('commande_id')
                ->constrained('commandes')
                ->cascadeOnDelete();

            /*
             * Le produit peut être supprimé plus tard.
             * On conserve donc quand même la ligne de commande.
             */
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            /*
             * Snapshot du produit au moment de la commande.
             * Cela permet de conserver l'ancien nom et l'ancien prix
             * même si le produit est modifié plus tard.
             */
            $table->string('designation');

            $table->unsignedInteger('quantite');

            $table->decimal('prix_unitaire', 15, 2);

            $table->decimal('total_ligne', 15, 2);

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('commande_items');
        Schema::dropIfExists('commandes');
    }
};
