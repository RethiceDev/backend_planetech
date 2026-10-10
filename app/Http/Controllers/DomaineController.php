<?php

namespace App\Http\Controllers;

use App\Models\Domaine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DomaineController extends Controller
{
    /**
     * Afficher la liste des domaines.
     */
    public function index()
    {
        $domaines = Domaine::latest()->get();

        return view('admin.domaine', compact('domaines'));
    }

    /**
     * Afficher le formulaire de création.
     */
    public function create()
    {
        $domaines = Domaine::latest()->get();

        return view('admin.domaine', compact('domaines'));
    }

    /**
     * Enregistrer un nouveau domaine.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
            ],

            'visuel' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:2048',
            ],
        ], [
            'titre.required' => 'Le titre est obligatoire.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',

            'description.required' => 'La description est obligatoire.',

            'visuel.mimes' => 'Le visuel doit être au format JPG, PNG, WebP ou SVG.',
            'visuel.max' => 'Le visuel ne doit pas dépasser 2 Mo.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Upload du visuel
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('visuel')) {
            $validated['visuel'] = $request
                ->file('visuel')
                ->store('domaines', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Création
        |--------------------------------------------------------------------------
        */

        Domaine::create($validated);

        return redirect()
            ->route('domaines.index')
            ->with('success', 'Le domaine a été ajouté avec succès.');
    }

    /**
     * Afficher un domaine.
     */
    public function show(Domaine $domaine)
    {
        return view('admin.domaine', [
            'domaines' => Domaine::latest()->get(),
            'domaine' => $domaine,
        ]);
    }

    /**
     * Afficher le formulaire de modification.
     */
    public function edit(Domaine $domaine)
    {
        $domaines = Domaine::latest()->get();

        return view('admin.domaine', compact('domaines', 'domaine'));
    }

    /**
     * Modifier un domaine.
     */
    public function update(Request $request, Domaine $domaine)
    {
        $validated = $request->validate([
            'titre' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
            ],

            'visuel' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:2048',
            ],
        ], [
            'titre.required' => 'Le titre est obligatoire.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',

            'description.required' => 'La description est obligatoire.',

            'visuel.mimes' => 'Le visuel doit être au format JPG, PNG, WebP ou SVG.',
            'visuel.max' => 'Le visuel ne doit pas dépasser 2 Mo.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Nouveau visuel
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('visuel')) {

            // Supprimer l'ancien visuel
            if ($domaine->visuel) {
                Storage::disk('public')->delete($domaine->visuel);
            }

            // Enregistrer le nouveau visuel
            $validated['visuel'] = $request
                ->file('visuel')
                ->store('domaines', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Mise à jour
        |--------------------------------------------------------------------------
        */

        $domaine->update($validated);

        return redirect()
            ->route('domaines.index')
            ->with('success', 'Le domaine a été modifié avec succès.');
    }

    /**
     * Supprimer un domaine.
     */
    public function destroy(Domaine $domaine)
    {
        /*
        |--------------------------------------------------------------------------
        | Supprimer le visuel
        |--------------------------------------------------------------------------
        */

        if ($domaine->visuel) {
            Storage::disk('public')->delete($domaine->visuel);
        }

        /*
        |--------------------------------------------------------------------------
        | Supprimer le domaine
        |--------------------------------------------------------------------------
        */

        $domaine->delete();

        return redirect()
            ->route('domaines.index')
            ->with('success', 'Le domaine a été supprimé avec succès.');
    }
}
