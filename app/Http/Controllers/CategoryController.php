<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Afficher les catégories.
     */




    public function index()
    {
        $categories = Category::withCount('produits')
            ->latest()
            ->get();

        return view('admin.categorie', compact('categories'));
    }

    /**
     * Afficher le formulaire d'ajout.
     */
    public function create()
    {
        $categories = Category::withCount('produits')
            ->latest()
            ->get();

        return view('admin.categorie', compact('categories'));
    }

    /**
     * Enregistrer une catégorie.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'nom' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:categories,nom',
                ],
            ],
            [
                'nom.required' => 'Le nom de la catégorie est obligatoire.',
                'nom.string' => 'Le nom de la catégorie doit être une chaîne de caractères.',
                'nom.max' => 'Le nom de la catégorie ne doit pas dépasser 255 caractères.',
                'nom.unique' => 'Cette catégorie existe déjà.',
            ]
        );

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'La catégorie a été ajoutée avec succès.');
    }

    /**
     * Afficher le formulaire de modification.
     */
    public function edit(Category $category)
    {
        $categories = Category::withCount('produits')
            ->latest()
            ->get();

        return view('admin.categorie', compact(
            'categories',
            'category'
        ));
    }

    /**
     * Modifier une catégorie.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate(
            [
                'nom' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:categories,nom,' . $category->id,
                ],
            ],
            [
                'nom.required' => 'Le nom de la catégorie est obligatoire.',
                'nom.string' => 'Le nom de la catégorie doit être une chaîne de caractères.',
                'nom.max' => 'Le nom de la catégorie ne doit pas dépasser 255 caractères.',
                'nom.unique' => 'Cette catégorie existe déjà.',
            ]
        );

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'La catégorie a été modifiée avec succès.');
    }

    /**
     * Supprimer une catégorie.
     */
    public function destroy(Category $category)
    {
        /*
         * On empêche la suppression si des produits
         * utilisent encore cette catégorie.
         */
        if ($category->produits()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with(
                    'error',
                    'Impossible de supprimer cette catégorie car elle contient encore des produits.'
                );
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'La catégorie a été supprimée avec succès.');
    }
}
