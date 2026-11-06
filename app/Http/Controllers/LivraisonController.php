<?php

namespace App\Http\Controllers;

use App\Models\CommandeItem;
use Illuminate\Http\Request;
use App\Models\Commande;
use App\Models\Livraison;
use App\Models\LivraisonItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class LivraisonController extends Controller
{
    public function enregistrerLivraison(Request $request, Commande $commande)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.commande_item_id' => ['required', 'exists:commande_items,id'],
            'items.*.quantite_livree' => ['required', 'integer', 'min:0'],
            'observation' => ['nullable', 'string'],
        ]);

        $livraison = DB::transaction(function () use ($commande, $validated) {
            // Générer un numéro de BL unique (ex: BL-2026-0001)
            $annee = date('Y');
            $dernierId = Livraison::max('id') + 1;
            $numeroBl = 'BL-' . $annee . '-' . str_pad($dernierId, 4, '0', STR_PAD_LEFT);

            $livraison = Livraison::create([
                'commande_id' => $commande->id,
                'numero_bl' => $numeroBl,
                'date_livraison' => now(),
                'observation' => $validated['observation'] ?? null,
            ]);

            $tousTotalementLivres = true;

            foreach ($validated['items'] as $itemData) {
                $commandeItem = CommandeItem::findOrFail($itemData['commande_item_id']);
                $quantiteSaisie = (int) $itemData['quantite_livree'];

                if ($quantiteSaisie <= 0) {
                    continue;
                }

                // Vérifier qu'on ne livre pas plus que le reste dû
                $resteRestant = $commandeItem->quantite - $commandeItem->quantite_livree;
                if ($quantiteSaisie > $resteRestant) {
                    throw new \Exception("La quantité livrée dépasse le reste à livrer pour l'article : {$commandeItem->designation}");
                }

                // Enregistrer la ligne de livraison
                LivraisonItem::create([
                    'livraison_id' => $livraison->id,
                    'commande_item_id' => $commandeItem->id,
                    'quantite_livree' => $quantiteSaisie,
                ]);

                // Mettre à jour la quantité livrée cumulée sur l'article
                $nouvelleQuantiteLivree = $commandeItem->quantite_livree + $quantiteSaisie;
                $commandeItem->update([
                    'quantite_livree' => $nouvelleQuantiteLivree,
                ]);

                // Vérifier si cet article est encore partiellement non livré
                if ($nouvelleQuantiteLivree < $commandeItem->quantite) {
                    $tousTotalementLivres = false;
                }
            }

            // Vérifier globalement si tous les articles de la commande sont totalement livrés
            foreach ($commande->items as $item) {
                if ($item->quantite_livree < $item->quantite) {
                    $tousTotalementLivres = false;
                    break;
                }
            }

            // Mettre à jour le statut de la commande
            $commande->update([
                'statut' => $tousTotalementLivres ? 'livree' : 'partiellement_livree',
            ]);

            return $livraison;
        });

        // 3. Génération du PDF du Bordereau de Livraison (BL)
        $pdfData = null;
        try {
            $pdfData = Pdf::loadView('pdf.livraison', [
                'commande' => $commande->load('items.produit'),
                'livraison' => $livraison->load('items.commandeItem'),
                'logo' => $this->logoBase64(),
            ])
            ->setPaper('a4', 'portrait')
            ->output();
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF BL : ' . $e->getMessage());
        }

        // Archiver le PDF sur le serveur local
        if ($pdfData !== null) {
            try {
                $cheminStockage = 'livraisons/' . preg_replace('/[^A-Za-z0-9_-]/', '-', $livraison->numero_bl) . '.pdf';
                Storage::disk('local')->put($cheminStockage, $pdfData);
            } catch (\Throwable $e) {
                Log::error('Erreur enregistrement BL sur le serveur : ' . $e->getMessage());
            }
        }

        // 4. Envoi optionnel du BL par e-mail au client (si l'e-mail est renseigné)
        if (!empty($commande->email) && $pdfData !== null) {
            try {
                // Mail::to($commande->email)->send(new LivraisonMail($commande, $livraison, $pdfData));
            } catch (\Throwable $e) {
                Log::error('Erreur envoi BL par email : ' . $e->getMessage());
            }
        }

        return redirect()->route('commandes.show', $commande->id)
            ->with('success', 'Bordereau de livraison ' . $livraison->numero_bl . ' enregistré et archivé avec succès.');
    }
}


