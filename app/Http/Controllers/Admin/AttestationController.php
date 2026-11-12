<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attestation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttestationController extends Controller
{
    public function index()
    {
        return view('admin.attestations.index', [
            'attestations' => Attestation::latest()->paginate(8),
            'types'        => Attestation::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        // Types qui concernent une personne avec une période
        $personne = 'fin_stage_professionnel,fin_stage_academique,travail';

        $data = $request->validate([
            'type'               => ['required', Rule::in(array_keys(Attestation::TYPES))],
            'numero'             => ['nullable', 'string', 'max:100'],
            'societe_emettrice'  => ['required', 'string', 'max:100'],
            'beneficiaire'       => ['required', 'string', 'max:150'],
            'genre'              => ['nullable', 'required_if:type,' . $personne, Rule::in(['M', 'F'])],
            'poste'              => ['nullable', 'required_if:type,' . $personne, 'string', 'max:150'],
            'date_debut'         => ['nullable', 'required_if:type,' . $personne, 'date'],
            'date_fin'           => ['nullable', 'required_if:type,' . $personne, 'date', 'after_or_equal:date_debut'],
            'duree'              => ['nullable', 'string', 'max:60'],
            'objet'              => ['nullable', 'required_if:type,bonne_fin_travaux', 'string', 'max:255'],
            'periode_texte'      => ['nullable', 'required_if:type,bonne_fin_travaux', 'string', 'max:100'],
            'date_etablissement' => ['required', 'date'],
        ]);

        $data['reference'] = 'ATT-' . now()->format('Y') . '-'
            . str_pad((string) ((Attestation::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);

        Attestation::create($data);

        return redirect()
            ->route('admin.attestations.index')
            ->with('success', 'Attestation enregistrée avec succès.');
    }

    public function view(Attestation $attestation)
    {
        return $this->pdf($attestation)->stream($attestation->reference . '.pdf');
    }

    public function download(Attestation $attestation)
    {
        return $this->pdf($attestation)->download($attestation->reference . '.pdf');
    }

    private function pdf(Attestation $attestation)
    {
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.attestations.pdf', compact('attestation'))
            ->setPaper('a4', 'portrait');
    }
}