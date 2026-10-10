<?php

namespace App\Http\Controllers;

use App\Models\Partenaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PartenaireController extends Controller
{
    /**
     * Afficher la liste des partenaires
     */
    public function index()
    {
        $partenaires = Partenaire::latest()->get();

        return view('admin.partenaire', compact('partenaires'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $partenaires = Partenaire::latest()->get();

        return view('admin.partenaire', compact('partenaires'));
    }

    /**
     * Enregistrer un partenaire
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'logo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:2048'
            ],
        ], [
            'nom.required' => 'Le nom du partenaire est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'site_web.url' => 'Veuillez saisir une adresse web valide.',
            'site_web.max' => 'Le site web ne doit pas dépasser 255 caractères.',
            'logo.mimes' => 'Le logo doit être au format JPG, PNG, WebP ou SVG.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ]);

        // Upload du logo
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')
                ->store('partenaires', 'public');
        }

        Partenaire::create($validated);

        return redirect()
            ->route('partenaires.index')
            ->with('success', 'Le partenaire a été ajouté avec succès.');
    }

    /**
     * Afficher un partenaire
     */
    public function show(Partenaire $partenaire)
    {
        $partenaires = Partenaire::latest()->get();

        return view('admin.partenaire', compact(
            'partenaires',
            'partenaire'
        ));
    }

    /**
     * Formulaire de modification
     */
    public function edit(Partenaire $partenaire)
    {
        $partenaires = Partenaire::latest()->get();

        return view('admin.partenaire', compact(
            'partenaires',
            'partenaire'
        ));
    }

    /**
     * Modifier un partenaire
     */
    public function update(Request $request, Partenaire $partenaire)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'logo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,svg',
                'max:2048'
            ],
        ], [
            'nom.required' => 'Le nom du partenaire est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'site_web.url' => 'Veuillez saisir une adresse web valide.',
            'site_web.max' => 'Le site web ne doit pas dépasser 255 caractères.',
            'logo.mimes' => 'Le logo doit être au format JPG, PNG, WebP ou SVG.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ]);

        // Si un nouveau logo est envoyé
        if ($request->hasFile('logo')) {

            // Supprimer l'ancien logo
            if ($partenaire->logo) {
                Storage::disk('public')->delete($partenaire->logo);
            }

            // Enregistrer le nouveau
            $validated['logo'] = $request->file('logo')
                ->store('partenaires', 'public');
        }

        $partenaire->update($validated);

        return redirect()
            ->route('partenaires.index')
            ->with('success', 'Le partenaire a été modifié avec succès.');
    }

    /**
     * Supprimer un partenaire
     */
    public function destroy(Partenaire $partenaire)
    {
        // Supprimer le logo
        if ($partenaire->logo) {
            Storage::disk('public')->delete($partenaire->logo);
        }

        // Supprimer le partenaire
        $partenaire->delete();

        return redirect()
            ->route('partenaires.index')
            ->with('success', 'Le partenaire a été supprimé avec succès.');
    }
}
