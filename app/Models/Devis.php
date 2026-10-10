<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devis extends Model
{
    use HasFactory;

    protected $table = 'devis';

    protected $fillable = [
        'demande_devis_id',
        'numero',
        'date_emission',
        'date_validite',
        'sous_total',
        'remise',
        'tva',
        'total_ttc',
        'fichier_pdf',
        'conditions_paiement',
        'delai_realisation',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_validite' => 'date',
        'sous_total' => 'decimal:2',
        'remise' => 'decimal:2',
        'tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    /**
     * Un devis appartient à une demande de devis.
     */
    public function demandeDevis()
    {
        return $this->belongsTo(
            DemandeDevis::class,
            'demande_devis_id'
        );
    }

    /**
     * Un devis possède plusieurs lignes.
     */
    public function lignes()
    {
        return $this->hasMany(
            LigneDevis::class,
            'devis_id'
        );
    }
}