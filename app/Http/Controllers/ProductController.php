<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SousCategorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    private const PER_PAGE = 10;

    /**
     * Données communes à index() et edit() (liste paginée + recherche).
     */
    private function listData(Request $request): array
    {
        $search = trim((string) $request->query('q', ''));

        // Découpe en mots : chaque mot doit être trouvé quelque part
        $words = array_filter(preg_split('/\s+/', $search));

        $products = Product::with('sousCategorie.category')
            ->when(!empty($words), function ($query) use ($words) {
                foreach ($words as $word) {
                    $like = '%' . addcslashes($word, '%_\\') . '%';

                    $query->where(function ($q) use ($like) {
                        $q->where('name', 'like', $like)
                          ->orWhere('description', 'like', $like)
                          ->orWhere('price', 'like', $like)
                          ->orWhereHas('sousCategorie', function ($sq) use ($like) {
                              $sq->where('nom', 'like', $like)
                                 ->orWhereHas('category', function ($cq) use ($like) {
                                     $cq->where('nom', 'like', $like);
                                 });
                          });
                    });
                }
            })
            ->orderBy('name', 'asc')
            ->paginate(4)
            ->onEachSide(1)
            ->withQueryString();

        $sousCategories = SousCategorie::with('category')
            ->orderBy('nom', 'asc')
            ->get();

        return [
            'products'       => $products,
            'sousCategories' => $sousCategories,
            'search'         => $search,
        ];
    }

    private function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'sous_categorie_id' => ['required', 'exists:sous_categories,id'],
            'price'             => ['nullable', 'numeric', 'min:0'],
            'description'       => ['required', 'string'],
            'fiche_technique'   => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'photo'             => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required'              => 'Le nom du produit est obligatoire.',
            'name.max'                   => 'Le nom ne doit pas dépasser 255 caractères.',
            'sous_categorie_id.required' => 'Veuillez sélectionner une sous-catégorie.',
            'sous_categorie_id.exists'   => 'La sous-catégorie sélectionnée est invalide.',
            'price.numeric'              => 'Le prix doit être un nombre valide.',
            'price.min'                  => 'Le prix ne peut pas être négatif.',
            'description.required'       => 'La description du produit est obligatoire.',
            'fiche_technique.file'       => 'La fiche technique doit être un fichier valide.',
            'fiche_technique.mimes'      => 'La fiche technique doit être un fichier PDF.',
            'fiche_technique.max'        => 'La fiche technique ne doit pas dépasser 5 Mo.',
            'photo.image'                => 'Le fichier sélectionné doit être une image.',
            'photo.mimes'                => 'La photo doit être au format JPG, JPEG, PNG ou WEBP.',
            'photo.max'                  => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }

    /**
     * Supprime un fichier local (ignore les URL externes).
     */
    private function deleteLocalFile(?string $path): void
    {
        if (!$path || preg_match('#^https?://#i', $path)) {
            return;
        }

        // "storage/products/x.jpg" -> "products/x.jpg"
        $path = preg_replace('#^/?storage/#', '', $path);

        Storage::disk('public')->delete($path);
    }

    public function index(Request $request)
    {
        return view('admin.boutique', $this->listData($request));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['stock'] = $request->boolean('stock');

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('products', 'public');
        }

        if ($request->hasFile('fiche_technique')) {
            $validated['fiche_technique'] = $request->file('fiche_technique')
                ->store('fiches-techniques', 'public');
        }

        Product::create($validated);

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit enregistré avec succès.');
    }

    public function edit(Request $request, Product $product)
    {
        return view('admin.boutique', array_merge(
            $this->listData($request),
            ['product' => $product]
        ));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['stock'] = $request->boolean('stock');

        if ($request->hasFile('photo')) {
            $this->deleteLocalFile($product->photo);
            $validated['photo'] = $request->file('photo')->store('products', 'public');
        } else {
            unset($validated['photo']);
        }

        if ($request->hasFile('fiche_technique')) {
            $this->deleteLocalFile($product->fiche_technique);
            $validated['fiche_technique'] = $request->file('fiche_technique')
                ->store('fiches-techniques', 'public');
        } else {
            unset($validated['fiche_technique']);
        }

        $product->update($validated);

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit modifié avec succès.');
    }

    public function destroy(Product $product)
    {
        $this->deleteLocalFile($product->photo);
        $this->deleteLocalFile($product->fiche_technique);

        $product->delete();

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit supprimé avec succès.');
    }
}
