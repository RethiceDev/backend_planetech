<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SousCategorie;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SousCategorieController extends Controller
{
    /**
     * Afficher la liste des sous-catégories.
     */
    public function index()
    {
        $sousCategories = SousCategorie::with('category')
            ->withCount('produits')
            ->orderBy('nom', 'asc')
            ->get();

        $categories = Category::orderBy('nom', 'asc')->get();

        return view('admin.sous-categorie', compact(
            'sousCategories',
            'categories'
        ));
    }

    /**
     * Afficher le formulaire de création.
     */
    public function create()
    {
        $sousCategories = SousCategorie::with('category')
            ->withCount('produits')
            ->orderBy('nom', 'asc')
            ->get();

        $categories = Category::orderBy('nom', 'asc')->get();

        return view('admin.sous-categorie', compact(
            'sousCategories',
            'categories'
        ));
    }

    /**
     * Enregistrer une sous-catégorie.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'category_id' => [
                    'required',
                    'exists:categories,id',
                ],

                'nom' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('sous_categories', 'nom')
                        ->where(function ($query) use ($request) {
                            return $query->where(
                                'category_id',
                                $request->category_id
                            );
                        }),
                ],
            ],
            [
                'category_id.required' =>
                    'Veuillez sélectionner une catégorie.',

                'category_id.exists' =>
                    'La catégorie sélectionnée n’existe pas.',

                'nom.required' =>
                    'Le nom de la sous-catégorie est obligatoire.',

                'nom.string' =>
                    'Le nom de la sous-catégorie doit être une chaîne de caractères.',

                'nom.max' =>
                    'Le nom de la sous-catégorie ne doit pas dépasser 255 caractères.',

                'nom.unique' =>
                    'Cette sous-catégorie existe déjà dans cette catégorie.',
            ]
        );

        SousCategorie::create($validated);

        return redirect()
            ->route('sous-categories.index')
            ->with(
                'success',
                'La sous-catégorie a été ajoutée avec succès.'
            );
    }

    /**
     * Afficher une sous-catégorie.
     */
    public function show(SousCategorie $sousCategorie)
    {
        $sousCategorie->load([
            'category',
            'produits',
        ]);

        return view(
            'admin.sous-categorie-show',
            compact('sousCategorie')
        );
    }

    /**
     * Afficher le formulaire de modification.
     */
    public function edit(SousCategorie $sousCategorie)
    {
        $sousCategories = SousCategorie::with('category')
            ->withCount('produits')
            ->orderBy('nom', 'asc')
            ->get();

        $categories = Category::orderBy('nom', 'asc')->get();

        return view('admin.sous-categorie', compact(
            'sousCategories',
            'categories',
            'sousCategorie'
        ));
    }

    /**
     * Modifier une sous-catégorie.
     */
    public function update(
        Request $request,
        SousCategorie $sousCategorie
    ) {
        $validated = $request->validate(
            [
                'category_id' => [
                    'required',
                    'exists:categories,id',
                ],

                'nom' => [
                    'required',
                    'string',
                    'max:255',

                    Rule::unique('sous_categories', 'nom')
                        ->where(function ($query) use ($request) {
                            return $query->where(
                                'category_id',
                                $request->category_id
                            );
                        })
                        ->ignore($sousCategorie->id),
                ],
            ],
            [
                'category_id.required' =>
                    'Veuillez sélectionner une catégorie.',

                'category_id.exists' =>
                    'La catégorie sélectionnée n’existe pas.',

                'nom.required' =>
                    'Le nom de la sous-catégorie est obligatoire.',

                'nom.string' =>
                    'Le nom de la sous-catégorie doit être une chaîne de caractères.',

                'nom.max' =>
                    'Le nom de la sous-catégorie ne doit pas dépasser 255 caractères.',

                'nom.unique' =>
                    'Cette sous-catégorie existe déjà dans cette catégorie.',
            ]
        );

        $sousCategorie->update($validated);

        return redirect()
            ->route('sous-categories.index')
            ->with(
                'success',
                'La sous-catégorie a été modifiée avec succès.'
            );
    }

    /**
     * Supprimer une sous-catégorie.
     */
    public function destroy(SousCategorie $sousCategorie)
    {
        if ($sousCategorie->produits()->exists()) {
            return redirect()
                ->route('sous-categories.index')
                ->with(
                    'error',
                    'Impossible de supprimer cette sous-catégorie car elle contient encore des produits.'
                );
        }

        $sousCategorie->delete();

        return redirect()
            ->route('sous-categories.index')
            ->with(
                'success',
                'La sous-catégorie a été supprimée avec succès.'
            );
    }
}
