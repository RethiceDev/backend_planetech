<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Procuration;
use App\Models\ProcurationAction;
use App\Mail\ProcurationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ProcurationController extends Controller
{
    public function index()
    {
        $procurations = Procuration::with('actions')->latest()->paginate(10);
        return view('admin.procurations', compact('procurations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_procurant'    => 'required|string|max:255',
            'cip_procurant'    => ['required', 'string', 'regex:/^\d{10}$/'], // Exactement 10 chiffres
            'expiration_cip'   => 'required|date',
            'role_procurant'   => 'required|string|max:255',
            'email_procurant'  => 'required|email|max:255',
            'date_debut'       => 'required|date',
            'date_fin'         => 'required|date|after_or_equal:date_debut',
            'actions'          => 'required|array|min:1',
            'actions.*'        => 'required|string|max:255',
        ], [
            'cip_procurant.regex' => 'Le CIP du procurant doit contenir exactement 10 chiffres.',
        ]);

        try {
            DB::beginTransaction();

            $procuration = Procuration::create([
                'nom_donnateur'   => 'Armelle ASSOGBA',
                'role_donnateur'  => 'Directeur Général',
                'nom_procurant'   => $validated['nom_procurant'],
                'cip_procurant'   => $validated['cip_procurant'],
                'expiration_cip'  => $validated['expiration_cip'],
                'role_procurant'  => $validated['role_procurant'],
                'email_procurant' => $validated['email_procurant'],
                'date_debut'      => $validated['date_debut'],
                'date_fin'        => $validated['date_fin'],
            ]);

            foreach ($validated['actions'] as $actionLibelle) {
                ProcurationAction::create([
                    'procuration_id' => $procuration->id,
                    'libelle_action' => $actionLibelle,
                ]);
            }

            DB::commit();
            $procuration->load('actions');

            // Envoi des e-mails avec PDF en pièce jointe
            Mail::to($procuration->email_procurant)->send(new ProcurationMail($procuration));

            $adminEmail = config('mail.from.address');
            if ($adminEmail && $adminEmail !== $procuration->email_procurant) {
                Mail::to($adminEmail)->send(new ProcurationMail($procuration));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la création : ' . $e->getMessage())->withInput();
        }

        return redirect()->route('admin.procurations.index')->with('success', 'Procuration établie et transmise par e-mail avec succès.');
    }

    // Voir le PDF dans le navigateur
    public function viewPdf($id)
    {
        $procuration = Procuration::with('actions')->findOrFail($id);
        $pdf = Pdf::loadView('admin.procurations.pdf', compact('procuration'));
        return $pdf->stream('Procuration-' . $procuration->reference . '.pdf');
    }

    // Télécharger le PDF
    public function downloadPdf($id)
    {
        $procuration = Procuration::with('actions')->findOrFail($id);
        $pdf = Pdf::loadView('admin.procurations.pdf', compact('procuration'));
        return $pdf->download('Procuration-' . $procuration->reference . '.pdf');
    }
}
