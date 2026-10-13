<?php

namespace App\Http\Controllers;

use App\Mail\CommandeDisponibiliteMail;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CommandeController extends Controller
{
    /**
     * Afficher la liste des commandes dans l'administration.
     */
    public function index(Request $request)
    {
        $recherche = trim((string) $request->input('recherche', ''));
        $statut = $request->input('statut', 'toutes');

        $query = Commande::query()
            ->with('items')
            ->latest();

        if ($recherche !== '') {
            $query->where(function ($q) use ($recherche) {
                $q->where('reference', 'like', "%{$recherche}%")
                    ->orWhere('nom', 'like', "%{$recherche}%")
                    ->orWhere('raison_sociale', 'like', "%{$recherche}%")
                    ->orWhere('email', 'like', "%{$recherche}%")
                    ->orWhere('telephone', 'like', "%{$recherche}%");
            });
        }

        switch ($statut) {
            case 'nouvelles':
                $query->where('statut', 'recue');
                break;

            case 'en_cours':
                $query->whereIn('statut', ['en_cours', 'en_traitement']);
                break;

            case 'terminees':
                $query->whereIn('statut', ['terminee', 'livree']);
                break;
        }

        $commandes = $query
            ->paginate(10)
            ->withQueryString();

        $totalCommandes = Commande::count();
        $nouvellesCommandes = Commande::where('statut', 'recue')->count();
        $commandesEnCours = Commande::whereIn('statut', ['en_cours', 'en_traitement'])->count();
        $commandesTerminees = Commande::whereIn('statut', ['terminee', 'livree'])->count();

        /*
        | Données du modal : uniquement les commandes de la page affichée.
        | Les prix et la disponibilité restent null tant que l'admin
        | n'a pas envoyé le devis.
        */
        $commandesData = [];

        foreach ($commandes as $commande) {
            $commandesData[$commande->id] = [
                'id' => $commande->id,
                'reference' => $commande->reference,
                'type_client' => $commande->type_client,
                'nom' => $commande->nom,
                'raison_sociale' => $commande->raison_sociale,
                'email' => $commande->email,
                'telephone' => $commande->telephone,
                'total' => $commande->total !== null ? (float) $commande->total : null,
                'statut' => $commande->statut,
                'items' => $commande->items
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'designation' => $item->designation,
                            'quantite' => (int) $item->quantite,
                            'prix_unitaire' => $item->prix_unitaire !== null
                                ? (float) $item->prix_unitaire
                                : null,
                            'total_ligne' => $item->total_ligne !== null
                                ? (float) $item->total_ligne
                                : null,
                            'disponible' => $item->disponible === null
                                ? null
                                : (bool) $item->disponible,
                        ];
                    })
                    ->values()
                    ->toArray(),
            ];
        }

        return view('admin.commande', [
            'commandes' => $commandes,
            'totalCommandes' => $totalCommandes,
            'nouvellesCommandes' => $nouvellesCommandes,
            'commandesEnCours' => $commandesEnCours,
            'commandesTerminees' => $commandesTerminees,
            'recherche' => $recherche,
            'statut' => $statut,
            'commandesData' => $commandesData,
        ]);
    }

    /**
     * Enregistre la disponibilité et les prix de chaque article,
     * calcule le total, puis envoie le devis au client par email.
     */
    public function envoyerDevis(Request $request, Commande $commande)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.disponible' => ['required', 'boolean'],
            'items.*.prix_unitaire' => ['nullable', 'numeric', 'min:0'],
        ]);

        $commande->load('items');

        if ($commande->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne contient aucun article.',
            ], 422);
        }

        // Toutes les lignes de la commande doivent être traitées,
        // et uniquement celles de cette commande.
        $idsCommande = $commande->items->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $idsRecus = collect($validated['items'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

        if ($idsCommande->all() !== $idsRecus->all()) {
            return response()->json([
                'success' => false,
                'message' => 'Les articles envoyés ne correspondent pas à cette commande.',
            ], 422);
        }

        // Un article disponible doit avoir un prix.
        foreach ($validated['items'] as $itemData) {
            $disponible = filter_var($itemData['disponible'], FILTER_VALIDATE_BOOLEAN);
            $prix = $itemData['prix_unitaire'] ?? null;

            if ($disponible && ($prix === null || $prix === '' || (float) $prix <= 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Renseignez un prix pour chaque article disponible.',
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($commande, $validated) {
                $total = 0;

                foreach ($validated['items'] as $itemData) {
                    $item = $commande->items->firstWhere('id', (int) $itemData['id']);

                    if (!$item) {
                        continue;
                    }

                    $disponible = filter_var($itemData['disponible'], FILTER_VALIDATE_BOOLEAN);

                    if ($disponible) {
                        $prix = (float) $itemData['prix_unitaire'];
                        $totalLigne = $prix * (int) $item->quantite;
                        $total += $totalLigne;

                        $item->update([
                            'disponible' => true,
                            'prix_unitaire' => $prix,
                            'total_ligne' => $totalLigne,
                        ]);
                    } else {
                        $item->update([
                            'disponible' => false,
                            'prix_unitaire' => null,
                            'total_ligne' => null,
                        ]);
                    }
                }

                $commande->update([
                    'total' => $total,
                    'statut' => 'en_traitement',
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement du devis.',
            ], 500);
        }

        $commande->refresh();
        $commande->load('items');

        if (empty($commande->email)) {
            return response()->json([
                'success' => true,
                'message' => 'Devis enregistré, mais ce client n\'a pas d\'adresse email.',
            ]);
        }

        try {
            Mail::to($commande->email)->send(new CommandeDisponibiliteMail($commande));
        } catch (\Throwable $e) {
            Log::error('Erreur envoi devis client : ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Devis enregistré, mais l\'email n\'a pas pu être envoyé au client. Réessayez.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Le devis a été enregistré et envoyé au client.',
        ]);
    }
}
