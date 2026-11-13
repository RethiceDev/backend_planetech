<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE decharges MODIFY type ENUM('argent','materiel','responsabilite','prestation') NOT NULL");

        Schema::table('decharges', function (Blueprint $table) {
            $table->string('role')->nullable()->change();             // facultatif pour un particulier
            $table->string('email')->nullable()->change();            // facultatif (déjà géré ainsi par le contrôleur)
            $table->longText('signature_data')->nullable()->change(); // signature désormais manuscrite

            $table->boolean('engagement_actif')->default(false);
            $table->string('engagement_type')->nullable();            // achat_materiel | prestation
            $table->text('engagement_objet')->nullable();
            $table->decimal('engagement_montant', 12, 2)->nullable();
        });

        Schema::create('decharge_tranches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decharge_id')->constrained('decharges')->onDelete('cascade');
            $table->enum('nature', ['paiement', 'livraison']);
            $table->string('libelle');
            $table->date('date_prevue')->nullable();
            $table->decimal('montant', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decharge_tranches');

        Schema::table('decharges', function (Blueprint $table) {
            $table->dropColumn(['engagement_actif', 'engagement_type', 'engagement_objet', 'engagement_montant']);
        });

        DB::statement("ALTER TABLE decharges MODIFY type ENUM('argent','materiel','responsabilite') NOT NULL");
    }
};
