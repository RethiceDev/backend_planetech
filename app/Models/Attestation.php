<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attestation extends Model
{
    public const TYPES = [
        'fin_stage_professionnel' => 'Fin de stage professionnel',
        'fin_stage_academique'    => 'Fin de stage académique',
        'travail'                 => 'Attestation de travail',
        'bonne_fin_travaux'       => "Bonne fin d'exécution des travaux",
    ];

    protected $fillable = [
        'reference', 'type', 'numero', 'societe_emettrice', 'beneficiaire',
        'genre', 'poste', 'date_debut', 'date_fin', 'duree', 'objet',
        'periode_texte', 'date_etablissement',
    ];

    protected $casts = [
        'date_debut'         => 'date',
        'date_fin'           => 'date',
        'date_etablissement' => 'date',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}