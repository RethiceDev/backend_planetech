<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
    'name',
    'sous_categorie_id',
    'price',
    'description',
    'fiche_technique',
    'photo',
    'stock',
];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'boolean',
    ];

    /**
     * Un produit appartient à une sous-catégorie.
     */
    public function sousCategorie()
    {
        return $this->belongsTo(
            SousCategorie::class,
            'sous_categorie_id'
        );
    }

    /**
     * Permet d'accéder à la catégorie
     * à travers la sous-catégorie.
     *
     * Exemple :
     * $product->category->nom
     */
    public function category()
    {
        return $this->hasOneThrough(
            Category::class,
            SousCategorie::class,
            'id',
            'id',
            'sous_categorie_id',
            'category_id'
        );
    }
}
