<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Toutes les catégories avec leurs sous-catégories (en un seul appel).
     *
     * GET /api/categories  (alias : GET /api/categorie)
     *
     * Réponse :
     * [
     *   { id, nom, sous_categories: [ { id, nom, category_id }, ... ] },
     *   ...
     * ]
     *
     * IMPORTANT : le modèle Category doit avoir la relation `sousCategories`
     * (voir la note en bas de ce fichier).
     */
    public function listes()
    {
        $categories = Category::with([
            'sousCategories' => function ($q) {
                $q->select('id', 'nom', 'category_id')->orderBy('nom');
            },
        ])
            ->orderBy('nom')
            ->get();

        return response()->json($categories);
    }
}

/*
 * ---------------------------------------------------------------
 * NOTE : dans app/Models/Category.php, vérifiez que cette relation
 * existe (adaptez le nom du modèle SousCategorie si besoin) :
 *
 *   public function sousCategories()
 *   {
 *       return $this->hasMany(SousCategorie::class, 'category_id');
 *   }
 *
 * Laravel la sérialise automatiquement en `sous_categories`,
 * ce que le frontend attend.
 * ---------------------------------------------------------------
 */
