<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DechargeMail;
use App\Models\Decharge;
use App\Models\DechargeItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class DechargeController extends Controller
{
    /**
     * Afficher la liste des décharges.
     */
    public function index()
    {
        $decharges = Decharge::with('items')
            ->latest()
            ->paginate(10);

        return view('admin.decharges', compact('decharges'));
    }

    /**
     * Enregistrer une nouvelle décharge.
     */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation des données
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            // Informations du bénéficiaire
            'nom' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'entreprise' => ['nullable', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],

            // Type de décharge
            'type' => [
                'required',
                'in:argent,materiel,responsabilite',
            ],

            // Fournisseur / livreur
            'fournisseur_nom' => [
                'required_if:type,materiel',
                'nullable',
                'string',
                'max:255',
            ],

            'fournisseur_entreprise' => [
                'nullable',
                'string',
                'max:255',
            ],

            'fournisseur_numero' => [
                'required_if:type,materiel',
                'nullable',
                'string',
                'max:50',
            ],

            'fournisseur_email' => [
                'required_if:type,materiel',
                'nullable',
                'email',
                'max:255',
            ],

            // Décharge d'argent
            'somme' => [
                'required_if:type,argent',
                'nullable',
                'numeric',
                'min:0',
            ],

            'motif' => [
                'required_if:type,argent',
                'nullable',
                'string',
                'max:255',
            ],

            // Décharge de responsabilité
            'description' => [
                'required_if:type,responsabilite',
                'nullable',
                'string',
                'max:10000',
            ],

            // Liste des matériels
            'materiels' => [
                'required_if:type,materiel',
                'nullable',
                'array',
                'min:1',
            ],

            'materiels.*.designation' => [
                'required_if:type,materiel',
                'nullable',
                'string',
                'max:255',
            ],

            'materiels.*.quantite' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'materiels.*.prix_unitaire' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'materiels.*.prix_global' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'materiels.*.etat' => [
                'required_if:type,materiel',
                'nullable',
                'in:neuf,partiellement_utilise,vieux,defectueux',
            ],

            // Signature numérique
            'signature_data' => [
                'required',
                'string',
                'regex:/^data:image\/png;base64,/',
            ],
        ]);

        $type = $validated['type'];

        /*
        |--------------------------------------------------------------------------
        | 2. Vérifier les matériels avant la transaction
        |--------------------------------------------------------------------------
        */

        if ($type === 'materiel') {
            $materiels = $validated['materiels'] ?? [];

            if (count($materiels) === 0) {
                throw ValidationException::withMessages([
                    'materiels' => 'Ajoutez au moins un matériel.',
                ]);
            }

            foreach ($materiels as $index => $item) {
                $designation = trim($item['designation'] ?? '');

                if ($designation === '') {
                    throw ValidationException::withMessages([
                        "materiels.$index.designation" =>
                            'La désignation du matériel est obligatoire.',
                    ]);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Enregistrement transactionnel
        |--------------------------------------------------------------------------
        */

        try {
            $decharge = DB::transaction(function () use (
                $validated,
                $type
            ) {
                $decharge = Decharge::create([
                    'nom' => trim($validated['nom']),
                    'role' => trim($validated['role']),

                    'entreprise' => !empty($validated['entreprise'])
                        ? trim($validated['entreprise'])
                        : null,

                    'numero' => trim($validated['numero']),
                    'email' => strtolower(trim($validated['email'])),

                    // Informations du fournisseur
                    'fournisseur_nom' => $type === 'materiel'
                        ? trim($validated['fournisseur_nom'])
                        : null,

                    'fournisseur_entreprise' => $type === 'materiel'
                        ? (
                            !empty($validated['fournisseur_entreprise'])
                                ? trim($validated['fournisseur_entreprise'])
                                : null
                        )
                        : null,

                    'fournisseur_numero' => $type === 'materiel'
                        ? trim($validated['fournisseur_numero'])
                        : null,

                    'fournisseur_email' => $type === 'materiel'
                        ? strtolower(trim($validated['fournisseur_email']))
                        : null,

                    // Type de décharge
                    'type' => $type,

                    // Informations financières
                    'somme' => $type === 'argent'
                        ? ($validated['somme'] ?? null)
                        : null,

                    'motif' => $type === 'argent'
                        ? ($validated['motif'] ?? null)
                        : null,

                    // Responsabilité
                    'description' => $type === 'responsabilite'
                        ? ($validated['description'] ?? null)
                        : null,

                    // Signature
                    'signature_data' => $validated['signature_data'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | 4. Enregistrer les lignes de matériels
                |--------------------------------------------------------------------------
                */

                if ($type === 'materiel') {
                    foreach ($validated['materiels'] as $item) {
                        $designation = trim($item['designation']);

                        $quantite = (
                            isset($item['quantite'])
                            && $item['quantite'] !== ''
                        )
                            ? (int) $item['quantite']
                            : null;

                        $prixUnitaire = (
                            isset($item['prix_unitaire'])
                            && $item['prix_unitaire'] !== ''
                        )
                            ? (float) $item['prix_unitaire']
                            : null;

                        $prixGlobal = (
                            isset($item['prix_global'])
                            && $item['prix_global'] !== ''
                        )
                            ? (float) $item['prix_global']
                            : null;

                        // Calculer le prix global uniquement s'il n'a
                        // pas été renseigné et si les deux valeurs
                        // nécessaires sont disponibles.
                        if (
                            $prixGlobal === null
                            && $prixUnitaire !== null
                            && $quantite !== null
                        ) {
                            $prixGlobal = round(
                                $prixUnitaire * $quantite,
                                2
                            );
                        }

                        DechargeItem::create([
                            'decharge_id' => $decharge->id,
                            'designation' => $designation,
                            'quantite' => $quantite,
                            'prix_unitaire' => $prixUnitaire,
                            'prix_global' => $prixGlobal,
                            'etat' => $item['etat'],
                        ]);
                    }
                }

                return $decharge;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error(
                'Erreur lors de l’enregistrement d’une décharge.',
                [
                    'type' => $type,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'fichier' => $e->getFile(),
                    'ligne' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return redirect()
                ->back()
                ->with(
                    'error',
                    "Une erreur est survenue lors de l'enregistrement de la décharge. Consultez les journaux Laravel pour identifier la cause."
                )
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Recharger les relations
        |--------------------------------------------------------------------------
        */

        $decharge->load('items');

        /*
        |--------------------------------------------------------------------------
        | 6. Préparer les destinataires
        |--------------------------------------------------------------------------
        */

        $emailPrincipal = $type === 'materiel'
            ? $decharge->fournisseur_email
            : $decharge->email;

        $destinataires = collect([
            $emailPrincipal,
            config('decharge.admin_email'),
        ])
            ->filter(fn ($mail) => is_string($mail) && trim($mail) !== '')
            ->map(fn ($mail) => strtolower(trim($mail)))
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 7. Envoyer les e-mails
        |--------------------------------------------------------------------------
        */

        $erreursMail = [];

        foreach ($destinataires as $destinataire) {
            try {
                Mail::to($destinataire)->send(
                    new DechargeMail($decharge)
                );
            } catch (Throwable $e) {
                Log::error(
                    'Échec de l’envoi de l’e-mail de décharge.',
                    [
                        'reference' => $decharge->reference,
                        'destinataire' => $destinataire,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'fichier' => $e->getFile(),
                        'ligne' => $e->getLine(),
                    ]
                );

                $erreursMail[] = $destinataire;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Message de retour
        |--------------------------------------------------------------------------
        */

        if (count($erreursMail) > 0) {
            return redirect()
                ->back()
                ->with(
                    'warning',
                    'La décharge '
                    . $decharge->reference
                    . ' a été enregistrée, mais certains e-mails n’ont pas pu être envoyés : '
                    . implode(', ', $erreursMail)
                );
        }

        return redirect()
            ->back()
            ->with(
                'success',
                'La décharge '
                . $decharge->reference
                . ' a été enregistrée et transmise par e-mail.'
            );
    }

    /**
     * Afficher ou télécharger le PDF d'une décharge.
     */
    public function pdf(Request $request, Decharge $decharge)
    {
        $decharge->load('items');

        $logo = null;

        $logoPath = public_path('images/logo.png');

        if (is_file($logoPath) && is_readable($logoPath)) {
            $logoContent = file_get_contents($logoPath);

            if ($logoContent !== false) {
                $logo = 'data:image/png;base64,'
                    . base64_encode($logoContent);
            }
        }

        $pdf = Pdf::loadView('pdf.decharge', [
            'decharge' => $decharge,
            'logo' => $logo,
        ])->setPaper('a4', 'portrait');

        $filename = ($decharge->reference ?: 'decharge-' . $decharge->id)
            . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
