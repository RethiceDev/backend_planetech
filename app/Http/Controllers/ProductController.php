<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\SousCategorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Afficher la boutique et la liste des produits.
     */
    public function index()
    {
        $products = Product::with('sousCategorie.category')
            ->orderBy('name', 'asc')
            ->get();

        $categories = Category::orderBy('nom', 'asc')
            ->get();

        $sousCategories = SousCategorie::with('category')
            ->orderBy('nom', 'asc')
            ->get();

        return view('admin.boutique', compact(
            'products',
            'categories',
            'sousCategories'
        ));
    }

    /**
     * Enregistrer un produit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'sous_categorie_id' => [
                    'required',
                    'exists:sous_categories,id',
                ],

                'price' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'description' => [
                    
                    'string',
                ],

                // Fiche technique facultative
                'fiche_technique' => [
                    'nullable',
                    'string',
                ],

                'photo' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
            ],
            [
                'name.required' =>
                    'Le nom du produit est obligatoire.',

                'name.max' =>
                    'Le nom ne doit pas dépasser 255 caractères.',

                'sous_categorie_id.required' =>
                    'Veuillez sélectionner une sous-catégorie.',

                'sous_categorie_id.exists' =>
                    'La sous-catégorie sélectionnée est invalide.',

                'price.numeric' =>
                    'Le prix doit être un nombre valide.',

                'price.min' =>
                    'Le prix ne peut pas être négatif.',

                'description.required' =>
                    'La description du produit est obligatoire.',

                'fiche_technique.string' =>
                    'La fiche technique doit être un texte valide.',

                'photo.image' =>
                    'Le fichier sélectionné doit être une image.',

                'photo.mimes' =>
                    'La photo doit être au format JPG, JPEG, PNG ou WEBP.',

                'photo.max' =>
                    'La photo ne doit pas dépasser 2 Mo.',
            ]
        );

        // Gestion du stock
        $validated['stock'] = $request->boolean('stock');

        // Upload de la photo
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')
                ->store('products', 'public');
        }

        // Création du produit
        Product::create($validated);

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit enregistré avec succès.');
    }

    /**
     * Afficher le formulaire de modification.
     */
    public function edit(Product $product)
    {
        $products = Product::with('sousCategorie.category')
            ->orderBy('name', 'asc')
            ->get();

        $categories = Category::orderBy('nom', 'asc')
            ->get();

        $sousCategories = SousCategorie::with('category')
            ->orderBy('nom', 'asc')
            ->get();

        return view('admin.boutique', compact(
            'product',
            'products',
            'categories',
            'sousCategories'
        ));
    }

    /**
     * Modifier un produit.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'sous_categorie_id' => [
                    'required',
                    'exists:sous_categories,id',
                ],

                'price' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'description' => [
                    'required',
                    'string',
                ],

                // Fiche technique facultative
                'fiche_technique' => [
                    'nullable',
                    'string',
                ],

                'photo' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
            ],
            [
                'name.required' =>
                    'Le nom du produit est obligatoire.',

                'name.max' =>
                    'Le nom ne doit pas dépasser 255 caractères.',

                'sous_categorie_id.required' =>
                    'Veuillez sélectionner une sous-catégorie.',

                'sous_categorie_id.exists' =>
                    'La sous-catégorie sélectionnée est invalide.',

                'price.numeric' =>
                    'Le prix doit être un nombre valide.',

                'price.min' =>
                    'Le prix ne peut pas être négatif.',

                'description.required' =>
                    'La description du produit est obligatoire.',

                'fiche_technique.string' =>
                    'La fiche technique doit être un texte valide.',

                'photo.image' =>
                    'Le fichier sélectionné doit être une image.',

                'photo.mimes' =>
                    'La photo doit être au format JPG, JPEG, PNG ou WEBP.',

                'photo.max' =>
                    'La photo ne doit pas dépasser 2 Mo.',
            ]
        );

        // Gestion du stock
        $validated['stock'] = $request->boolean('stock');

        // Si une nouvelle photo est envoyée
        if ($request->hasFile('photo')) {

            // Supprimer l'ancienne photo
            if ($product->photo) {
                Storage::disk('public')->delete($product->photo);
            }

            // Enregistrer la nouvelle photo
            $validated['photo'] = $request->file('photo')
                ->store('products', 'public');
        }

        // Mise à jour du produit
        // La fiche technique existante est conservée si
        // le champ n'est pas envoyé dans le formulaire.
        $product->update($validated);

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit modifié avec succès.');
    }

    /**
     * Supprimer un produit.
     */
    public function destroy(Product $product)
    {
        // Supprimer la photo du stockage
        if ($product->photo) {
            Storage::disk('public')->delete($product->photo);
        }

        // Supprimer le produit
        $product->delete();

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit supprimé avec succès.');
    }
}
