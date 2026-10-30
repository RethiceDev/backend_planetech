<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    /**
     * Liste tous les produits sans restriction de stock ou de prix.
     *
     * GET /api/produits
     */
    public function listes(Request $request)
    {
        $validated = $request->validate([
            'category_id'       => 'nullable|integer',
            'sous_categorie_id' => 'nullable|integer',
            'search'            => 'nullable|string|max:100',
            'per_page'          => 'nullable|integer|min:1|max:100',
            'page'              => 'nullable|integer|min:1',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 24);

        $query = Product::with(['sousCategorie.category']);

        // Filtre par catégorie principale
        if ($request->filled('category_id')) {
            $categoryId = $validated['category_id'];

            $query->whereHas('sousCategorie.category', function ($q) use ($categoryId) {
                $q->where('id', $categoryId);
            });
        }

        // Filtre par sous-catégorie
        if ($request->filled('sous_categorie_id')) {
            $query->where('sous_categorie_id', $validated['sous_categorie_id']);
        }

        // Recherche texte
        if ($request->filled('search')) {
            $terme = trim($validated['search']);

            $terme = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $terme);
            $like  = '%' . $terme . '%';

            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('sousCategorie', function ($sq) use ($like) {
                        $sq->where('nom', 'like', $like)
                            ->orWhereHas('category', function ($cq) use ($like) {
                                $cq->where('nom', 'like', $like);
                            });
                    });
            });
        }

        // Tri stable et pagination
        $products = $query
            ->orderBy('sous_categorie_id')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Détail d'un produit.
     *
     * GET /api/produits/{product}
     */
    public function show(Product $product)
    {
        $product->load(['sousCategorie.category']);

        return response()->json([
            'data' => $product,
        ]);
    }
}
