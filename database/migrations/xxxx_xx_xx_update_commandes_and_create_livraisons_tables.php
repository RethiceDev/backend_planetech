<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajout des champs du Bon de Commande (BC) et de la Facture finale dans 'commandes'
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('numero_bc')->nullable()->after('statut');
            $table->date('date_bc')->nullable()->after('numero_bc');
            $table->text('observation_bc')->nullable()->after('date_bc');

            // Pour la phase de clôture / comptabilité
            $table->string('reference_facture')->nullable()->after('observation_bc');
            $table->string('delai_paiement')->nullable()->after('reference_facture');
        });

        // 2. Ajout du suivi des quantités livrées dans les lignes de commande
        Schema::table('commande_items', function (Blueprint $table) {
            $table->integer('quantite_livree')->default(0)->after('quantite');
        });

        // 3. Création de la table des Bordereaux de Livraison (BL)
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->string('numero_bl')->unique(); // Ex: BL-2026-0001
            $table->date('date_livraison');
            $table->text('observation')->nullable();
            $table->timestamps();
        });

        // 4. Création de la table des lignes de livraison (ce qui a été réellement livré par BL)
        Schema::create('livraison_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livraison_id')->constrained('livraisons')->onDelete('cascade');
            $table->foreignId('commande_item_id')->constrained('commande_items')->onDelete('cascade');
            $table->integer('quantite_livree'); // Quantité livrée sur ce bordereau précis
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livraison_items');
        Schema::dropIfExists('livraisons');

        Schema::table('commande_items', function (Blueprint $table) {
            $table->dropColumn('quantite_livree');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn([
                'numero_bc',
                'date_bc',
                'observation_bc',
                'reference_facture',
                'delai_paiement',
            ]);
        });
    }
};
