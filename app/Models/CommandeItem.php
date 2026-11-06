<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandeItem extends Model
{
    use HasFactory;

    protected $table = 'commande_items';
// Dans App\Models\CommandeItem

    protected $fillable = [
        'commande_id',
        'product_id',
        'designation',
        'quantite',
        'quantite_livree',
        'prix_unitaire',
        'total_ligne',
        'disponible',
    ];

    /**
     * Accesseur pour obtenir automatiquement le reste à livrer (quantite - quantite_livree).
     */
    public function getQuantiteRestanteAttribute(): int
    {
        return max(0, (int) $this->quantite - (int) $this->quantite_livree);
    }
    
protected $casts = [
    'prix_unitaire' => 'decimal:2',
    'total_ligne' => 'decimal:2',
    'disponible' => 'boolean',
];

    /**
     * La ligne appartient à une commande.
     */
    public function commande()
    {
        return $this->belongsTo(
            Commande::class,
            'commande_id'
        );
    }

    /**
     * La ligne peut être liée à un produit.
     */
    public function produit()
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }
}
