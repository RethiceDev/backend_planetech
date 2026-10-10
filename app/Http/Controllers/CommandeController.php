<?php

namespace App\Http\Controllers;

use App\Mail\CommandeDisponibiliteMail;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        /*
        |--------------------------------------------------------------------------
        | REQUÊTE PRINCIPALE
        |--------------------------------------------------------------------------
        */

        $query = Commande::query()
            ->with('items')
            ->latest();

        /*
        |--------------------------------------------------------------------------
        | RECHERCHE
        |--------------------------------------------------------------------------
        */

        if ($recherche !== '') {

            $query->where(function ($q) use ($recherche) {

                $q->where('reference', 'like', "%{$recherche}%")
                    ->orWhere('nom', 'like', "%{$recherche}%")
                    ->orWhere('raison_sociale', 'like', "%{$recherche}%")
                    ->orWhere('email', 'like', "%{$recherche}%")
                    ->orWhere('telephone', 'like', "%{$recherche}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRE PAR STATUT
        |--------------------------------------------------------------------------
        */

        switch ($statut) {

            case 'nouvelles':

                $query->where('statut', 'recue');

                break;

            case 'en_cours':

                $query->whereIn('statut', [
                    'en_cours',
                    'en_traitement',
                ]);

                break;

            case 'terminees':

                $query->whereIn('statut', [
                    'terminee',
                    'livree',
                ]);

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        |
        | 10 commandes par page.
        | withQueryString() conserve :
        | - recherche
        | - statut
        | lorsque l'utilisateur change de page.
        |
        */

        $commandes = $query
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | STATISTIQUES GLOBALES
        |--------------------------------------------------------------------------
        */

        $totalCommandes = Commande::count();

        $nouvellesCommandes = Commande::where(
            'statut',
            'recue'
        )->count();

        $commandesEnCours = Commande::whereIn(
            'statut',
            [
                'en_cours',
                'en_traitement',
            ]
        )->count();

        $commandesTerminees = Commande::whereIn(
            'statut',
            [
                'terminee',
                'livree',
            ]
        )->count();

        /*
        |--------------------------------------------------------------------------
        | DONNÉES POUR LE MODAL
        |--------------------------------------------------------------------------
        |
        | Important :
        | commandesData contient uniquement les commandes
        | de la page actuellement affichée.
        |
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

                'total' => (float) $commande->total,

                'statut' => $commande->statut,

                'items' => $commande->items
                    ->map(function ($item) {

                        return [

                            'id' => $item->id,

                            'designation' => $item->designation,

                            'quantite' => (int) $item->quantite,

                            'prix_unitaire' => (float) $item->prix_unitaire,

                            'total_ligne' => (float) $item->total_ligne,

                            'disponible' => (bool) $item->disponible,
                        ];
                    })
                    ->values()
                    ->toArray(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | VUE
        |--------------------------------------------------------------------------
        */

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
     * Confirmer que tous les produits de la commande
     * sont disponibles.
     */
    public function confirmerDisponibilite(
        Request $request,
        Commande $commande
    ) {
        try {

            $commande->load('items');

            /*
            |--------------------------------------------------------------------------
            | Vérifier les articles
            |--------------------------------------------------------------------------
            */

            if ($commande->items->isEmpty()) {

                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne contient aucun article.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Mise à jour
            |--------------------------------------------------------------------------
            */

            DB::transaction(function () use ($commande) {

                foreach ($commande->items as $item) {

                    $item->update([
                        'disponible' => true,
                    ]);
                }

                $commande->update([
                    'statut' => 'en_traitement',
                ]);
            });

            /*
            |--------------------------------------------------------------------------
            | Recharger
            |--------------------------------------------------------------------------
            */

            $commande->refresh();
            $commande->load('items');

            /*
            |--------------------------------------------------------------------------
            | Envoyer le mail
            |--------------------------------------------------------------------------
            */

            if (!empty($commande->email)) {

                Mail::to($commande->email)->send(
                    new CommandeDisponibiliteMail($commande)
                );
            }

            return response()->json([

                'success' => true,

                'message' =>
                    'La disponibilité a été confirmée et le client a été informé.',

            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([

                'success' => false,

                'message' =>
                    'Une erreur est survenue lors de la confirmation de la disponibilité.',

            ], 500);
        }
    }


    /**
     * Enregistrer la disponibilité de chaque article
     * et informer automatiquement le client.
     */
    public function signalerIndisponibilite(
        Request $request,
        Commande $commande
    ) {
        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.id' => [
                'required',
                'integer',
                'distinct',
                'exists:commande_items,id',
            ],

            'items.*.disponible' => [
                'required',
                'boolean',
            ],

        ]);

        try {

            $commande->load('items');

            /*
            |--------------------------------------------------------------------------
            | Vérifier les articles
            |--------------------------------------------------------------------------
            */

            if ($commande->items->isEmpty()) {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'Cette commande ne contient aucun article.',

                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Vérifier que les articles appartiennent
            | bien à la commande
            |--------------------------------------------------------------------------
            */

            $itemsCommande = $commande->items
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            foreach ($validated['items'] as $itemData) {

                if (!$itemsCommande->contains((int) $itemData['id'])) {

                    return response()->json([

                        'success' => false,

                        'message' =>
                            'Un article envoyé ne correspond pas à cette commande.',

                    ], 422);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Mise à jour des disponibilités
            |--------------------------------------------------------------------------
            */

            DB::transaction(function () use ($commande, $validated) {

                foreach ($validated['items'] as $itemData) {

                    $item = $commande->items->firstWhere(
                        'id',
                        (int) $itemData['id']
                    );

                    if (!$item) {
                        continue;
                    }

                    $item->update([
                        'disponible' => (bool) $itemData['disponible'],
                    ]);
                }

                $commande->update([
                    'statut' => 'en_traitement',
                ]);
            });

            /*
            |--------------------------------------------------------------------------
            | Recharger
            |--------------------------------------------------------------------------
            */

            $commande->refresh();
            $commande->load('items');

            /*
            |--------------------------------------------------------------------------
            | Envoyer le mail
            |--------------------------------------------------------------------------
            */

            if (!empty($commande->email)) {

                Mail::to($commande->email)->send(
                    new CommandeDisponibiliteMail($commande)
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Réponse
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => true,

                'message' =>
                    'Les disponibilités ont été enregistrées et le client a été informé.',

            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([

                'success' => false,

                'message' =>
                    'Une erreur est survenue lors de l\'envoi au client.',

            ], 500);
        }
    }
}
