<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'nom',
    ];

    /**
     * Une catégorie possède plusieurs sous-catégories.
     */
    public function sousCategories()
    {
        return $this->hasMany(
            SousCategorie::class,
            'category_id'
        );
    }

    /**
     * Une catégorie possède plusieurs produits
     * via ses sous-catégories.
     */
    public function produits()
    {
        return $this->hasManyThrough(
            Product::class,
            SousCategorie::class,
            'category_id',
            'sous_categorie_id',
            'id',
            'id'
        );
    }
}