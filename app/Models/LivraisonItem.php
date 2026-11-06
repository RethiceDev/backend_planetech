<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LivraisonItem extends Model
{
    use HasFactory;

    protected $table = 'livraison_items';

    protected $fillable = [
        'livraison_id',
        'commande_item_id',
        'quantite_livree',
    ];

    /**
     * L'article livré appartient à un bordereau de livraison.
     */
    public function livraison()
    {
        return $this->belongsTo(Livraison::class, 'livraison_id');
    }

    /**
     * L'article livré fait référence à la ligne de commande initiale.
     */
    public function commandeItem()
    {
        return $this->belongsTo(CommandeItem::class, 'commande_item_id');
    }
}
