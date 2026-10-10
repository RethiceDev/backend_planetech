<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeDevis extends Model
{
    use HasFactory;

    protected $table = 'demandes_devis';

    protected $fillable = [
        'reference',
        'type_client',
        'raison_sociale',
        'nom',
        'email',
        'indicatif',
        'telephone',
        'domaine',
        'besoins',
        'statut',
    ];

    protected $attributes = [
        'statut' => 'en_attente',
    ];

    protected static function booted()
    {
        static::creating(function ($demande) {
            if (empty($demande->reference)) {
                $demande->reference = 'DEV-' . strtoupper(uniqid());
            }
        });
    }

    /**
     * Une demande peut avoir un devis commercial.
     */
    public function devis()
    {
        return $this->hasOne(Devis::class);
    }
}