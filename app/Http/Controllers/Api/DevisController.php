<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\DevisConfirmationMail;
use App\Models\DemandeDevis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DevisController extends Controller
{
    /**
     * Enregistrer une nouvelle demande de devis.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_client' => [
                'required',
                'in:societe,particulier',
            ],

            'raison_sociale' => [
                'required_if:type_client,societe',
                'nullable',
                'string',
                'max:255',
            ],

            'nom' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'indicatif' => [
                'required',
                'string',
                'max:10',
            ],

            'telephone' => [
                'required',
                'string',
                'max:50',
            ],

            'domaine' => [
                'required',
                'string',
                'max:255',
            ],

            'besoins' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Création de la demande
        |--------------------------------------------------------------------------
        */

        $demande = DemandeDevis::create($validated);

        /*
        |--------------------------------------------------------------------------
        | Email de confirmation au client
        |--------------------------------------------------------------------------
        */

        try {
            Mail::to($demande->email)
                ->send(new DevisConfirmationMail($demande));

            Log::info('Email de confirmation client envoyé.', [
                'demande_id' => $demande->id,
                'email' => $demande->email,
                'reference' => $demande->reference,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur email de confirmation client.', [
                'demande_id' => $demande->id,
                'email' => $demande->email,
                'reference' => $demande->reference,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Notification interne à PLANETECH
        |--------------------------------------------------------------------------
        |
        | L'adresse de réception est récupérée depuis MAIL_FROM_ADDRESS
        | dans le fichier .env, via la configuration Laravel.
        |
        */

        $adresseNotification = trim(
            (string) config('mail.from.address')
        );

        // Normaliser les adresses pour comparer sans tenir compte
        // des majuscules ou des espaces.
        $emailClient = strtolower(trim($demande->email));
        $emailNotification = strtolower($adresseNotification);

        if ($adresseNotification === '') {
            Log::warning(
                'Notification PLANETECH non envoyée : MAIL_FROM_ADDRESS non configurée.',
                [
                    'demande_id' => $demande->id,
                    'reference' => $demande->reference,
                ]
            );
        } elseif ($emailClient === $emailNotification) {
            /*
             * Si le client utilise la même adresse que celle configurée
             * pour PLANETECH, ne pas lui envoyer une deuxième copie.
             */
            Log::info(
                'Notification interne ignorée : le client utilise déjà cette adresse.',
                [
                    'demande_id' => $demande->id,
                    'reference' => $demande->reference,
                    'email' => $adresseNotification,
                ]
            );
        } else {
            try {
                $contenu =
                    "Nouvelle demande de devis PLANETECH\n\n" .
                    "Référence : {$demande->reference}\n" .
                    "Type de client : {$demande->type_client}\n" .
                    "Société : " .
                    ($demande->raison_sociale ?: 'Non renseignée') . "\n" .
                    "Nom : {$demande->nom}\n" .
                    "Email client : {$demande->email}\n" .
                    "Téléphone : {$demande->indicatif} {$demande->telephone}\n" .
                    "Domaine : {$demande->domaine}\n\n" .
                    "Besoins du client :\n" .
                    $demande->besoins;

                Mail::raw(
                    $contenu,
                    function ($message) use (
                        $demande,
                        $adresseNotification
                    ) {
                        $message
                            ->to($adresseNotification)
                            ->replyTo(
                                $demande->email,
                                $demande->nom
                            )
                            ->subject(
                                'Nouvelle demande de devis - ' .
                                $demande->reference
                            );
                    }
                );

                Log::info('Notification PLANETECH envoyée.', [
                    'demande_id' => $demande->id,
                    'email' => $adresseNotification,
                    'reference' => $demande->reference,
                ]);
            } catch (\Throwable $e) {
                Log::error('Erreur notification PLANETECH.', [
                    'demande_id' => $demande->id,
                    'email' => $adresseNotification,
                    'reference' => $demande->reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Réponse API
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Demande de devis enregistrée avec succès.',
            'reference' => $demande->reference,
            'demande' => $demande,
        ], 201);
    }

    /**
     * Lister toutes les demandes de devis.
     */
    public function listes_devis()
    {
        $demandes = DemandeDevis::latest()->get();

        return response()->json($demandes);
    }

    /**
     * Modifier le statut d'une demande de devis.
     */
    public function updateStatut(
        Request $request,
        DemandeDevis $demandeDevis
    ) {
        $validated = $request->validate([
            'statut' => [
                'required',
                'string',
                'max:50',
            ],
        ]);

        $demandeDevis->update($validated);

        return response()->json([
            'message' => 'Statut de la demande mis à jour avec succès.',
            'demande' => $demandeDevis,
        ]);
    }

    /**
     * Supprimer une demande de devis.
     */
    public function destroy(DemandeDevis $demandeDevis)
    {
        $demandeDevis->delete();

        return response()->json([
            'message' => 'Demande de devis supprimée avec succès.',
        ]);
    }
}
