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
        $perPage = request()->input('per_page', 24); // 24 produits par page par défaut

        $products = Product::with([
                'sousCategorie.category',
            ])
            ->where('stock', true)
            ->orderBy('name', 'asc')
            ->paginate($perPage);

        return response()->json($products);
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

