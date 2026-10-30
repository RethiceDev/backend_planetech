<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Decharge extends Model
{
    protected $fillable = [
        'reference',

        // Informations générales
        'nom',
        'role',
        'entreprise',
        'numero',
        'email',

        // Informations du fournisseur/livreur
        'fournisseur_nom',
        'fournisseur_entreprise',
        'fournisseur_numero',
        'fournisseur_email',

        // Décharge
        'type',
        'somme',
        'motif',
        'description',
        'signature_data',
    ];

    protected function casts(): array
    {
        return [
            'somme' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($decharge) {
            if (empty($decharge->reference)) {
                $next = ((int) static::max('id')) + 1;

                $decharge->reference = 'DEC-'
                    . date('Ym')
                    . '-'
                    . str_pad($next, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(DechargeItem::class);
    }
}
