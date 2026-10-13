<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    /**
     * Liste paginée et filtrée des produits de la boutique.
     *
     * GET /api/produits
     *
     * Paramètres (tous optionnels) :
     * - category_id       : id de la catégorie principale
     * - sous_categorie_id : id de la sous-catégorie
     * - search            : mot-clé (nom, description, sous-catégorie, catégorie)
     * - page              : numéro de la page
     * - per_page          : nombre de produits par page (24 par défaut, 100 max)
     *
     * Réponse : objet paginé Laravel
     * { data: [...], current_page, last_page, per_page, total, links: [...] }
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

        $query = Product::with(['sousCategorie.category'])
            ->where('stock', true);

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

            // Échapper les jokers LIKE saisis par l'utilisateur
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

        // Tri stable : indispensable pour que la pagination ne répète
        // ni ne saute aucun produit d'une page à l'autre.
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
