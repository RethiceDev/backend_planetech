<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneDevis extends Model
{
    use HasFactory;

    protected $table = 'lignes_devis';

    protected $fillable = [
        'devis_id',
        'designation',
        'quantite',
        'prix_unitaire',
        'total_ligne',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'total_ligne' => 'decimal:2',
    ];

    /**
     * La ligne appartient à un devis.
     */
    public function devis()
    {
        return $this->belongsTo(Devis::class);
    }
}