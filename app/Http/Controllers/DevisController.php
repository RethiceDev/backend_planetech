<?php

namespace App\Http\Controllers;

use App\Mail\DevisEnvoye;
use App\Models\DemandeDevis;
use App\Models\Devis;
use App\Models\LigneDevis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class DevisController extends Controller
{
    /**
     * Liste des demandes de devis.
     */
    public function listes(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | PARAMÈTRES DES FILTRES
        |--------------------------------------------------------------------------
        */

        $recherche = trim((string) $request->input('recherche', ''));

        $statut = $request->input('statut', 'toutes');

        $typeClient = $request->input('type_client', 'all');

        $domaine = trim((string) $request->input('domaine', ''));


        /*
        |--------------------------------------------------------------------------
        | NORMALISATION DES PARAMÈTRES
        |--------------------------------------------------------------------------
        */

        $statutsAutorises = [
            'toutes',
            'en_attente',
            'en_cours',
            'traite',
            'aujourd_hui',
            'cette_semaine',
            'annule',
        ];

        if (!in_array($statut, $statutsAutorises, true)) {
            $statut = 'toutes';
        }


        $typesClientsAutorises = [
            'all',
            'societe',
            'particulier',
        ];

        if (!in_array($typeClient, $typesClientsAutorises, true)) {
            $typeClient = 'all';
        }


        /*
        |--------------------------------------------------------------------------
        | REQUÊTE PRINCIPALE
        |--------------------------------------------------------------------------
        */

        $query = DemandeDevis::query()
            ->with('devis')
            ->latest();


        /*
        |--------------------------------------------------------------------------
        | RECHERCHE
        |--------------------------------------------------------------------------
        */

        if ($recherche !== '') {

            $query->where(function ($q) use ($recherche) {

                $q->where('reference', 'like', "%{$recherche}%")
                    ->orWhere('raison_sociale', 'like', "%{$recherche}%")
                    ->orWhere('nom', 'like', "%{$recherche}%")
                    ->orWhere('email', 'like', "%{$recherche}%")
                    ->orWhere('telephone', 'like', "%{$recherche}%")
                    ->orWhere('domaine', 'like', "%{$recherche}%");
            });
        }


        /*
        |--------------------------------------------------------------------------
        | TYPE DE CLIENT
        |--------------------------------------------------------------------------
        */

        if ($typeClient !== 'all') {

            $query->where(
                'type_client',
                $typeClient
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DOMAINE
        |--------------------------------------------------------------------------
        */

        if ($domaine !== '') {

            $query->where(
                'domaine',
                'like',
                "%{$domaine}%"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTRE PAR STATUT / DATE
        |--------------------------------------------------------------------------
        */

        switch ($statut) {

            case 'en_attente':

                $query->where(
                    'statut',
                    'en_attente'
                );

                break;


            case 'en_cours':

                $query->where(
                    'statut',
                    'en_cours'
                );

                break;


            case 'traite':

                $query->whereIn(
                    'statut',
                    [
                        'chiffree',
                        'envoye',
                        'traite',
                    ]
                );

                break;


            case 'annule':

                $query->where(
                    'statut',
                    'annule'
                );

                break;


            case 'aujourd_hui':

                $query
                    ->whereIn(
                        'statut',
                        [
                            'en_attente',
                            'en_cours',
                        ]
                    )
                    ->whereDate(
                        'created_at',
                        now()->toDateString()
                    );

                break;


            case 'cette_semaine':

                $query
                    ->whereIn(
                        'statut',
                        [
                            'en_attente',
                            'en_cours',
                        ]
                    )
                    ->whereBetween(
                        'created_at',
                        [
                            now()->startOfWeek(),
                            now()->endOfWeek(),
                        ]
                    );

                break;
        }


        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        |
        | 10 demandes par page.
        | withQueryString() conserve les filtres lorsque
        | l'utilisateur change de page.
        |
        */

        $listes = $query
            ->paginate(10)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | STATISTIQUES GLOBALES
        |--------------------------------------------------------------------------
        */

        $totalDemandes =
            DemandeDevis::count();


        $demandesNonTraitees =
            DemandeDevis::where(
                'statut',
                'en_attente'
            )->count();


        $demandesEnCours =
            DemandeDevis::where(
                'statut',
                'en_cours'
            )->count();


        $demandesTraitees =
            DemandeDevis::whereIn(
                'statut',
                [
                    'chiffree',
                    'envoye',
                    'traite',
                ]
            )->count();


        $demandesAnnulees =
            DemandeDevis::where(
                'statut',
                'annule'
            )->count();


        /*
        |--------------------------------------------------------------------------
        | DEMANDES DU JOUR
        |--------------------------------------------------------------------------
        |
        | Comme dans ta vue actuelle :
        | seules les demandes non chiffrées sont comptées.
        |
        */

        $demandesAujourdHui =
            DemandeDevis::whereIn(
                'statut',
                [
                    'en_attente',
                    'en_cours',
                ]
            )
            ->whereDate(
                'created_at',
                now()->toDateString()
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | DEMANDES DE CETTE SEMAINE
        |--------------------------------------------------------------------------
        */

        $demandesCetteSemaine =
            DemandeDevis::whereIn(
                'statut',
                [
                    'en_attente',
                    'en_cours',
                ]
            )
            ->whereBetween(
                'created_at',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | VUE
        |--------------------------------------------------------------------------
        */

        return view('admin.devis', [

            'listes' => $listes,

            'totalDemandes' => $totalDemandes,

            'demandesNonTraitees' => $demandesNonTraitees,

            'demandesEnCours' => $demandesEnCours,

            'demandesTraitees' => $demandesTraitees,

            'demandesAnnulees' => $demandesAnnulees,

            'demandesAujourdHui' => $demandesAujourdHui,

            'demandesCetteSemaine' => $demandesCetteSemaine,

            'recherche' => $recherche,

            'statut' => $statut,

            'typeClient' => $typeClient,

            'domaine' => $domaine,
        ]);
    }


    /**
     * Afficher le formulaire de création du devis.
     */
    public function creerDevis(DemandeDevis $demandeDevis)
    {
        $demande = $demandeDevis;

        return view(
            'admin.creer-devis',
            compact('demande')
        );
    }


    /**
     * Enregistrer et envoyer le devis.
     */
    public function store(
        Request $request,
        DemandeDevis $demandeDevis
    ) {
        $validated = $request->validate([

            'date_emission' => [
                'required',
                'date',
            ],

            'date_validite' => [
                'required',
                'date',
                'after_or_equal:date_emission',
            ],

            'remise' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'tva' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'delai_realisation' => [
                'nullable',
                'string',
                'max:255',
            ],

            'conditions_paiement' => [
                'nullable',
                'string',
            ],

            'lignes' => [
                'required',
                'array',
                'min:1',
            ],

            'lignes.*.designation' => [
                'required',
                'string',
                'max:255',
            ],

            'lignes.*.quantite' => [
                'required',
                'numeric',
                'min:1',
            ],

            'lignes.*.prix_unitaire' => [
                'required',
                'numeric',
                'min:0',
            ],

        ], [

            'date_emission.required' =>
                'La date d’émission est obligatoire.',

            'date_validite.required' =>
                'La date de validité est obligatoire.',

            'date_validite.after_or_equal' =>
                'La date de validité doit être supérieure ou égale à la date d’émission.',

            'lignes.required' =>
                'Veuillez ajouter au moins une ligne au devis.',

            'lignes.min' =>
                'Veuillez ajouter au moins une ligne au devis.',

            'lignes.*.designation.required' =>
                'La désignation de chaque ligne est obligatoire.',

            'lignes.*.quantite.required' =>
                'La quantité est obligatoire.',

            'lignes.*.quantite.min' =>
                'La quantité doit être supérieure à zéro.',

            'lignes.*.prix_unitaire.required' =>
                'Le prix unitaire est obligatoire.',

            'lignes.*.prix_unitaire.min' =>
                'Le prix unitaire ne peut pas être négatif.',
        ]);


        /*
        |--------------------------------------------------------------------------
        | VÉRIFICATION EMAIL
        |--------------------------------------------------------------------------
        */

        if (
            empty($demandeDevis->email) ||
            !filter_var(
                $demandeDevis->email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Impossible d’envoyer le devis : l’adresse e-mail du client est absente ou invalide.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | CALCULS
        |--------------------------------------------------------------------------
        */

        $sousTotal = 0;

        foreach ($validated['lignes'] as $ligne) {

            $quantite =
                (float) $ligne['quantite'];

            $prixUnitaire =
                (float) $ligne['prix_unitaire'];

            $sousTotal +=
                $quantite * $prixUnitaire;
        }


        $remise =
            (float) (
                $validated['remise'] ?? 0
            );

        $remise =
            min(
                max(0, $remise),
                $sousTotal
            );


        $baseTva =
            max(
                0,
                $sousTotal - $remise
            );


        $tauxTva =
            (float) (
                $validated['tva'] ?? 0
            );


        $montantTva =
            $baseTva * ($tauxTva / 100);


        $totalTtc =
            $baseTva + $montantTva;


        /*
        |--------------------------------------------------------------------------
        | NUMÉRO DU DEVIS
        |--------------------------------------------------------------------------
        */

        $dateNumero =
            now()->format('Ymd');


        $dernierDevis =
            Devis::where(
                'numero',
                'like',
                'DEV-' . $dateNumero . '-%'
            )
            ->orderByDesc('id')
            ->first();


        if ($dernierDevis) {

            $parties =
                explode(
                    '-',
                    $dernierDevis->numero
                );

            $dernierNumero =
                (int) end($parties);

            $prochainNumero =
                $dernierNumero + 1;

        } else {

            $prochainNumero = 1;
        }


        do {

            $numero =
                'DEV-' .
                $dateNumero .
                '-' .
                str_pad(
                    $prochainNumero,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

            $prochainNumero++;

        } while (
            Devis::where(
                'numero',
                $numero
            )->exists()
        );


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            $devis = Devis::create([

                'demande_devis_id' =>
                    $demandeDevis->id,

                'numero' =>
                    $numero,

                'date_emission' =>
                    $validated['date_emission'],

                'date_validite' =>
                    $validated['date_validite'],

                'sous_total' =>
                    $sousTotal,

                'remise' =>
                    $remise,

                'tva' =>
                    $tauxTva,

                'total_ttc' =>
                    $totalTtc,

                'conditions_paiement' =>
                    $validated['conditions_paiement'] ?? null,

                'delai_realisation' =>
                    $validated['delai_realisation'] ?? null,
            ]);


            foreach ($validated['lignes'] as $ligne) {

                $quantite =
                    (float) $ligne['quantite'];

                $prixUnitaire =
                    (float) $ligne['prix_unitaire'];

                $totalLigne =
                    $quantite * $prixUnitaire;


                LigneDevis::create([

                    'devis_id' =>
                        $devis->id,

                    'designation' =>
                        $ligne['designation'],

                    'quantite' =>
                        $quantite,

                    'prix_unitaire' =>
                        $prixUnitaire,

                    'total_ligne' =>
                        $totalLigne,
                ]);
            }


            $demandeDevis->update([
                'statut' => 'chiffree',
            ]);


            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Une erreur est survenue lors de l’enregistrement du devis : ' .
                    $e->getMessage()
                );
        }


        /*
        |--------------------------------------------------------------------------
        | ENVOI DU DEVIS PAR EMAIL
        |--------------------------------------------------------------------------
        */

        try {

            Mail::to(
                $demandeDevis->email
            )->send(
                new DevisEnvoye($devis)
            );


            $demandeDevis->update([
                'statut' => 'envoye',
            ]);


            return redirect()
                ->route('admin.devis')
                ->with(
                    'success',
                    'Le devis ' .
                    $devis->numero .
                    ' a été enregistré et envoyé avec succès à ' .
                    $demandeDevis->email .
                    '.'
                );

        } catch (\Throwable $mailException) {

            report($mailException);


            $demandeDevis->update([
                'statut' => 'chiffree',
            ]);


            return redirect()
                ->route('admin.devis')
                ->with(
                    'error',
                    'Le devis ' .
                    $devis->numero .
                    ' a été enregistré, mais l’e-mail a échoué : ' .
                    $mailException->getMessage()
                );
        }
    }


    /**
     * Afficher le PDF du devis dans le navigateur.
     */
    public function voirPdf(
        DemandeDevis $demandeDevis
    ) {
        $devis =
            $demandeDevis->devis()
                ->with([
                    'lignes',
                    'demandeDevis',
                ])
                ->first();


        if (!$devis) {

            abort(
                404,
                'Aucun devis n’a été enregistré pour cette demande.'
            );
        }


        $pdf = Pdf::loadView(

            'emails.devis-pdf',

            [
                'devis' =>
                    $devis,

                'demande' =>
                    $demandeDevis,
            ]

        )->setPaper(
            'a4',
            'portrait'
        );


        return $pdf->stream(
            $devis->numero . '.pdf'
        );
    }


    /**
     * Supprimer une demande de devis.
     */
    public function destroy(
        DemandeDevis $demandeDevis
    ) {
        $demandeDevis->delete();


        return response()->json([

            'message' =>
                'Demande de devis supprimée avec succès.',

        ]);
    }
}
