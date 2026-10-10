<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SousCategorie extends Model
{
    use HasFactory;

    protected $table = 'sous_categories';

    protected $fillable = [
        'category_id',
        'nom',
    ];

    /**
     * Une sous-catégorie appartient à une catégorie.
     */
    public function category()
    {
        return $this->belongsTo(
            Category::class,
            'category_id'
        );
    }

    /**
     * Une sous-catégorie possède plusieurs produits.
     */
    public function produits()
    {
        return $this->hasMany(
            Product::class,
            'sous_categorie_id'
        );
    }
}