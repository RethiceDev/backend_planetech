<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    use HasFactory;

    protected $table = 'livraisons';

    protected $fillable = [
        'commande_id',
        'numero_bl',
        'date_livraison',
        'observation',
    ];

    protected $casts = [
        'date_livraison' => 'date',
    ];

    /**
     * La livraison appartient à une commande.
     */
    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    /**
     * Une livraison contient plusieurs articles livrés.
     */
    public function items()
    {
        return $this->hasMany(LivraisonItem::class, 'livraison_id');
    }
}
