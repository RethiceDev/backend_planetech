<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $table = 'commandes';

    // Dans App\Models\Commande

    protected $fillable = [
        'reference',
        'type_client',
        'nom',
        'raison_sociale',
        'email',
        'telephone',
        'total',
        'statut',
        'numero_bc',
        'date_bc',
        'observation_bc',
        'reference_facture',
        'delai_paiement',
    ];

    /**
     * Une commande peut avoir plusieurs bordereaux de livraison (partiels ou total).
     */
    public function livraisons()
    {
        return $this->hasMany(Livraison::class, 'commande_id');
    }
    protected $casts = [
        'total' => 'decimal:2',
        'date_bc' => 'date',
    ];

    /**
     * Une commande possède plusieurs lignes.
     */
    public function items()
    {
        return $this->hasMany(
            CommandeItem::class,
            'commande_id'
        );
    }

}
