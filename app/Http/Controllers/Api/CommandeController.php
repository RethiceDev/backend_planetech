<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CommandeAdminMail;
use App\Mail\CommandeConfirmationMail;
use App\Models\Commande;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CommandeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_client' => ['required', 'in:particulier,entreprise'],
            'nom' => ['required', 'string', 'max:255'],
            'raison_sociale' => ['required_if:type_client,entreprise', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['required', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantite' => ['required', 'integer', 'min:1'],
        ]);

        $commande = DB::transaction(function () use ($validated) {
            do {
                $reference = 'CMD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
            } while (Commande::where('reference', $reference)->exists());

            $commande = Commande::create([
                'reference' => $reference,
                'type_client' => $validated['type_client'],
                'nom' => $validated['nom'],
                'raison_sociale' => $validated['type_client'] === 'entreprise'
                    ? $validated['raison_sociale']
                    : null,
                'email' => $validated['email'],
                'telephone' => $validated['telephone'],
                // Le total sera calculé par l'admin lors de l'envoi du devis.
                'total' => null,
                'statut' => 'recue',
            ]);

            foreach ($validated['items'] as $item) {
                $produit = Product::findOrFail($item['product_id']);

                $commande->items()->create([
                    'product_id' => $produit->id,
                    'designation' => $produit->name,
                    'quantite' => (int) $item['quantite'],
                    // Prix et disponibilité renseignés plus tard par l'admin.
                    'prix_unitaire' => null,
                    'total_ligne' => null,
                    'disponible' => null,
                ]);
            }

            return $commande;
        });

        $commande->load('items');

        /*
        |--------------------------------------------------------------------------
        | GÉNÉRATION DU PDF (bon de commande, sans prix)
        |--------------------------------------------------------------------------
        */
        $pdfData = null;

        try {
            $pdf = Pdf::loadView('pdf.commande', ['commande' => $commande])
                ->setPaper('a4', 'portrait');

            $pdfData = $pdf->output();
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF commande: ' . $e->getMessage());
        }

        /*
        |--------------------------------------------------------------------------
        | EMAIL PLANETECH (ADMIN)
        |--------------------------------------------------------------------------
        */
        if ($pdfData !== null) {
            try {
                $emailPlanetech = config('mail.admin_address') ?? config('mail.from.address');

                Mail::to($emailPlanetech)->send(new CommandeAdminMail($commande, $pdfData));

                Log::info('Email PLANETECH envoyé avec succès à ' . $emailPlanetech);
            } catch (\Throwable $e) {
                Log::error('Erreur envoi email PLANETECH : ' . $e->getMessage());
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EMAIL CLIENT (confirmation de réception, sans prix)
        |--------------------------------------------------------------------------
        */
        try {
            Mail::to($commande->email)->send(new CommandeConfirmationMail($commande));

            Log::info('Email client envoyé avec succès à ' . $commande->email);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi email client : ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Votre demande a bien été enregistrée. Nous vous contacterons avec la disponibilité et les prix.',
            'reference' => $commande->reference,
            'commande' => $commande,
        ], 201);
    }
}
