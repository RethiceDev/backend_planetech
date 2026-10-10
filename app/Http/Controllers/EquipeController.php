<?php

namespace App\Http\Controllers;

use App\Models\Equipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EquipeController extends Controller
{
    /**
     * Afficher la liste des membres.
     */
    public function index()
    {
        $equipes = Equipe::latest()->get();

        return view('admin.equipe', compact('equipes'));
    }

    /**
     * Afficher le formulaire d'ajout.
     */
    public function create()
    {
        $equipes = Equipe::latest()->get();

        return view('admin.equipe', compact('equipes'));
    }

    /**
     * Enregistrer un nouveau membre.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
            ],

            'role' => [
                'required',
                'string',
                'max:255',
            ],

            'pole' => [
                'required',
                'string',
                'max:255',
            ],

            'photo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:2048',
            ],
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',

            'role.required' => 'La fonction est obligatoire.',
            'role.max' => 'La fonction ne doit pas dépasser 255 caractères.',

            'pole.required' => 'Le pôle est obligatoire.',
            'pole.max' => 'Le pôle ne doit pas dépasser 255 caractères.',

            'photo.mimes' => 'La photo doit être au format JPG, PNG, WebP ou SVG.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Upload de la photo
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request
                ->file('photo')
                ->store('equipes', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Création
        |--------------------------------------------------------------------------
        */

        Equipe::create($validated);

        return redirect()
            ->route('equipes.index')
            ->with('success', 'Le membre a été ajouté avec succès.');
    }

    /**
     * Afficher un membre.
     */
    public function show(Equipe $equipe)
    {
        return view('admin.equipe', [
            'equipes' => Equipe::latest()->get(),
            'equipe' => $equipe,
        ]);
    }

    /**
     * Afficher le formulaire de modification.
     */
    public function edit(Equipe $equipe)
    {
        $equipes = Equipe::latest()->get();

        return view('admin.equipe', compact('equipes', 'equipe'));
    }

    /**
     * Modifier un membre.
     */
    public function update(Request $request, Equipe $equipe)
    {
        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
            ],

            'role' => [
                'required',
                'string',
                'max:255',
            ],

            'pole' => [
                'required',
                'string',
                'max:255',
            ],

            'photo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:2048',
            ],
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',

            'role.required' => 'La fonction est obligatoire.',
            'role.max' => 'La fonction ne doit pas dépasser 255 caractères.',

            'pole.required' => 'Le pôle est obligatoire.',
            'pole.max' => 'Le pôle ne doit pas dépasser 255 caractères.',

            'photo.mimes' => 'La photo doit être au format JPG, PNG, WebP ou SVG.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Nouvelle photo
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('photo')) {

            // Supprimer l'ancienne photo
            if ($equipe->photo) {
                Storage::disk('public')->delete($equipe->photo);
            }

            // Enregistrer la nouvelle photo
            $validated['photo'] = $request
                ->file('photo')
                ->store('equipes', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Mise à jour
        |--------------------------------------------------------------------------
        */

        $equipe->update($validated);

        return redirect()
            ->route('equipes.index')
            ->with('success', 'Le membre a été modifié avec succès.');
    }

    /**
     * Supprimer un membre.
     */
    public function destroy(Equipe $equipe)
    {
        // Supprimer la photo
        if ($equipe->photo) {
            Storage::disk('public')->delete($equipe->photo);
        }

        // Supprimer le membre
        $equipe->delete();

        return redirect()
            ->route('equipes.index')
            ->with('success', 'Le membre a été supprimé avec succès.');
    }
}
