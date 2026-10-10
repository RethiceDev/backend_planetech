<?php

namespace App\Http\Controllers;

use App\Models\Realisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RealisationController extends Controller
{
    /**
     * Affiche la page de gestion des réalisations.
     */
    public function index()
    {
        $realisations = Realisation::query()
            ->orderBy('ordre')
            ->latest()
            ->get();

        return view('admin.realisations', [
            'realisations' => $realisations,
            'realisation' => null,
        ]);
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Realisation $realisation)
    {
        $realisations = Realisation::query()
            ->orderBy('ordre')
            ->latest()
            ->get();

        return view('admin.realisations', [
            'realisations' => $realisations,
            'realisation' => $realisation,
        ]);
    }

    /**
     * Enregistre une nouvelle réalisation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'domaine' => [
                'required',
                'string',
                'max:150',
            ],

            'titre' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
                'max:2000',
            ],

            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'ordre' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ], [
            'domaine.required' =>
                'Le domaine est obligatoire.',

            'titre.required' =>
                'Le titre est obligatoire.',

            'description.required' =>
                'La description est obligatoire.',

            'image.required' =>
                'Veuillez sélectionner une image.',

            'image.image' =>
                'Le fichier sélectionné doit être une image.',

            'image.mimes' =>
                'L’image doit être au format JPG, JPEG, PNG ou WebP.',

            'image.max' =>
                'L’image ne doit pas dépasser 5 Mo.',
        ]);

        $validated['ordre'] =
            (int) ($validated['ordre'] ?? 0);

        $validated['actif'] =
            $request->boolean('actif');

        $validated['image'] =
            $request
                ->file('image')
                ->store('realisations', 'public');

        Realisation::create($validated);

        return redirect()
            ->route('realisations.index')
            ->with(
                'success',
                'La réalisation a été ajoutée avec succès.'
            );
    }

    /**
     * Met à jour une réalisation.
     */
    public function update(
        Request $request,
        Realisation $realisation
    ) {
        $validated = $request->validate([
            'domaine' => [
                'required',
                'string',
                'max:150',
            ],

            'titre' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
                'max:2000',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'ordre' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ], [
            'domaine.required' =>
                'Le domaine est obligatoire.',

            'titre.required' =>
                'Le titre est obligatoire.',

            'description.required' =>
                'La description est obligatoire.',

            'image.image' =>
                'Le fichier sélectionné doit être une image.',

            'image.mimes' =>
                'L’image doit être au format JPG, JPEG, PNG ou WebP.',

            'image.max' =>
                'L’image ne doit pas dépasser 5 Mo.',
        ]);

        $validated['ordre'] =
            (int) ($validated['ordre'] ?? 0);

        $validated['actif'] =
            $request->boolean('actif');

        /*
        |--------------------------------------------------------------------------
        | Nouvelle image
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('image')) {
            $ancienneImage = $realisation->image;

            $validated['image'] =
                $request
                    ->file('image')
                    ->store('realisations', 'public');

            if ($ancienneImage) {
                Storage::disk('public')->delete(
                    $ancienneImage
                );
            }
        }

        $realisation->update($validated);

        return redirect()
            ->route('realisations.index')
            ->with(
                'success',
                'La réalisation a été modifiée avec succès.'
            );
    }

    /**
     * Supprime une réalisation.
     */
    public function destroy(
        Realisation $realisation
    ) {
        if ($realisation->image) {
            Storage::disk('public')->delete(
                $realisation->image
            );
        }

        $realisation->delete();

        return redirect()
            ->route('realisations.index')
            ->with(
                'success',
                'La réalisation a été supprimée avec succès.'
            );
    }
}
