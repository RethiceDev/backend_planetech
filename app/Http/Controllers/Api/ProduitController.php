<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProduitController extends Controller
{
    /**
     * Liste des produits disponibles dans la boutique.
     *
     * Chaque produit est chargé avec :
     * - sa sous-catégorie
     * - la catégorie/domaine de sa sous-catégorie
     */
    public function listes()
    {
        $products = Product::with([
            'sousCategorie.category',
        ])
            ->where('stock', true)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * Détail d'un produit.
     */
    public function show(Product $product)
    {
        $product->load([
            'sousCategorie.category',
        ]);

        return response()->json([
            'data' => $product,
        ]);
    }
}

