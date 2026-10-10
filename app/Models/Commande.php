<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $table = 'commandes';

    protected $fillable = [
        'reference',
        'type_client',
        'nom',
        'raison_sociale',
        'email',
        'telephone',
        'total',
        'statut',
    ];

    protected $casts = [
        'total' => 'decimal:2',
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
