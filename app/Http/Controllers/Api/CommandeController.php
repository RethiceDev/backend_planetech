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
use Illuminate\Validation\ValidationException;

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
                'raison_sociale' => $validated['type_client'] === 'entreprise' ? $validated['raison_sociale'] : null,
                'email' => $validated['email'],
                'telephone' => $validated['telephone'],
                'total' => 0,
                'statut' => 'recue',
            ]);

            $totalCommande = 0;

            foreach ($validated['items'] as $item) {
                $produit = Product::query()->whereKey($item['product_id'])->lockForUpdate()->first();

                if (!$produit || !$produit->stock || $produit->price === null) {
                    throw ValidationException::withMessages([
                        'items' => ['Un des produits sélectionnés n’est plus disponible ou est sur devis.'],
                    ]);
                }

                $quantite = (int) $item['quantite'];
                $prixUnitaire = (float) $produit->price;
                $totalLigne = $prixUnitaire * $quantite;

                $commande->items()->create([
                    'product_id' => $produit->id,
                    'designation' => $produit->name,
                    'quantite' => $quantite,
                    'prix_unitaire' => $prixUnitaire,
                    'total_ligne' => $totalLigne,
                ]);

                $totalCommande += $totalLigne;
            }

            $commande->update(['total' => $totalCommande]);

            return $commande;
        });

        $commande->load('items');

        /*
        |--------------------------------------------------------------------------
        | GÉNÉRATION DU PDF
        |--------------------------------------------------------------------------
        */
        $pdfData = null;
        try {
            $pdf = Pdf::loadView('pdf.commande', ['commande' => $commande])->setPaper('a4', 'portrait');
            $pdfData = $pdf->output();
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF commande: ' . $e->getMessage());
        }

        /*
        |--------------------------------------------------------------------------
        | ENVOI EMAIL PLANETECH (ADMIN) - TEST ENVOI DIRECT (send)
        |--------------------------------------------------------------------------
        */
        if ($pdfData !== null) {
            try {
                $emailPlanetech = config('mail.admin_address') ?? config('mail.from.address');

                // Utilisation de ->send() au lieu de ->queue() pour tester instantanément
                Mail::to($emailPlanetech)->send(new CommandeAdminMail($commande, $pdfData));

                Log::info('Email PLANETECH envoyé avec succès à ' . $emailPlanetech);
            } catch (\Throwable $e) {
                Log::error('Erreur envoi email PLANETECH : ' . $e->getMessage());
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ENVOI EMAIL CLIENT - TEST ENVOI DIRECT (send)
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
            'message' => 'Votre commande a été enregistrée avec succès.',
            'reference' => $commande->reference,
            'commande' => $commande,
        ], 201);
    }
}
