<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Decharge;
use App\Models\DechargeItem;
use App\Mail\DechargeMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DechargeController extends Controller
{
    public function index()
    {
        $decharges = Decharge::with('items')->latest()->paginate(10);
        return view('admin.decharges', compact('decharges'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'        => 'required|string|max:255',
            'role'       => 'required|string|max:255',
            'entreprise' => 'nullable|string|max:255',
            'numero'     => 'required|string|max:50',
            'email'      => 'required|email|max:255',
            'type'       => 'required|in:argent,materiel,responsabilite',

            'somme'       => 'required_if:type,argent|nullable|numeric|min:0',
            'motif'       => 'required_if:type,argent|nullable|string|max:255',
            'description' => 'required_if:type,responsabilite|nullable|string',

            'materiels'               => 'required_if:type,materiel|nullable|array',
            'materiels.*.designation' => 'required_if:type,materiel|nullable|string|max:255',
            'materiels.*.quantite'    => 'required_if:type,materiel|nullable|integer|min:1',
            'materiels.*.etat'        => 'required_if:type,materiel|nullable|in:neuf,partiellement_utilise,vieux,defectueux',

            'signature_data' => 'required|string',
        ]);

        $type = $validated['type'];

        // ---------- 1. ENREGISTREMENT EN BASE ----------
        try {
            DB::beginTransaction();

            $decharge = Decharge::create([
                'nom'            => $validated['nom'],
                'role'           => $validated['role'],
                'entreprise'     => $validated['entreprise'] ?? null,
                'numero'         => $validated['numero'],
                'email'          => $validated['email'],
                'type'           => $type,
                'somme'          => $type === 'argent' ? ($validated['somme'] ?? null) : null,
                'motif'          => $type === 'argent' ? ($validated['motif'] ?? null) : null,
                'description'    => $type === 'responsabilite' ? ($validated['description'] ?? null) : null,
                'signature_data' => $validated['signature_data'],
            ]);

            if ($type === 'materiel') {
                $count = 0;
                foreach ($validated['materiels'] ?? [] as $item) {
                    if (empty($item['designation'])) {
                        continue;
                    }
                    DechargeItem::create([
                        'decharge_id' => $decharge->id,
                        'designation' => $item['designation'],
                        'quantite'    => $item['quantite'] ?? 1,
                        'etat'        => $item['etat'] ?? 'neuf',
                    ]);
                    $count++;
                }

                if ($count === 0) {
                    throw new \RuntimeException('Ajoutez au moins un matériel avec une désignation.');
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur enregistrement décharge : ' . $e->getMessage());

            return back()
                ->with('error', "Erreur lors de l'enregistrement : " . $e->getMessage())
                ->withInput();
        }

        // ---------- 2. ENVOI DES E-MAILS ----------
        $decharge->load('items');

        $destinataires = collect([
            $decharge->email,
            config('decharge.admin_email'),
        ])
            ->filter()
            ->map(fn ($mail) => strtolower(trim($mail)))
            ->unique()
            ->values();

        $erreursMail = [];

        foreach ($destinataires as $to) {
            try {
                Mail::to($to)->send(new DechargeMail($decharge));
            } catch (\Throwable $e) {
                Log::error("Échec envoi décharge {$decharge->reference} à {$to} : " . $e->getMessage());
                $erreursMail[] = $to . ' (' . $e->getMessage() . ')';
            }
        }

        if (!empty($erreursMail)) {
            return back()->with(
                'error',
                'Décharge ' . $decharge->reference . ' enregistrée, mais e-mail non envoyé à : ' . implode(' ; ', $erreursMail)
            );
        }

        return back()->with(
            'success',
            'La décharge ' . $decharge->reference . ' a été établie et transmise par e-mail avec succès.'
        );
    }

    /**
     * Affiche (ou télécharge avec ?download=1) le PDF d'une décharge.
     */
    public function pdf(Request $request, Decharge $decharge)
    {
        $decharge->load('items');

        // Logo en base64 (DomPDF le lit sans accès réseau)
        $logo = null;
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            $logo = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('pdf.decharge', [
            'decharge' => $decharge,
            'logo'     => $logo,
        ])->setPaper('a4', 'portrait');

        $filename = $decharge->reference . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}
