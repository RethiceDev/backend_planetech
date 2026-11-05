<?php

namespace App\Http\Controllers;

use App\Models\Entretien;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistreController extends Controller
{
    /**
     * Affiche le formulaire du registre d'arrivée.
     */
    public function create(): View
    {
        return view('recrutement.registre');
    }

    /**
     * Valide et enregistre l'arrivée d'un candidat.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom'     => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'poste'   => ['required', 'string', 'max:150'],
        ], [
            'required' => 'Ce champ est obligatoire.',
            'max'      => 'Ce champ est trop long.',
        ]);

        Entretien::create($data);

        return redirect()->route('recrutement.create')
            ->with('success', 'Merci, votre arrivée a bien été notée. Un recruteur va vous accueillir.');
    }

    /**
     * Admin : historique complet des arrivées.
     */
    public function index(Request $request): View
    {
        $recherche = trim((string) $request->query('recherche', ''));

        $entretiens = Entretien::query()
            ->when($recherche !== '', function ($query) use ($recherche) {
                $query->where(function ($q) use ($recherche) {
                    $q->where('nom', 'like', "%{$recherche}%")
                      ->orWhere('prenoms', 'like', "%{$recherche}%")
                      ->orWhere('poste', 'like', "%{$recherche}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalEntretiens = Entretien::count();
        $entretiensAujourdhui = Entretien::whereDate('created_at', today())->count();
        $entretiensSemaine = Entretien::where('created_at', '>=', now()->subDays(7))->count();

        return view('admin.entretiens', compact(
            'entretiens',
            'recherche',
            'totalEntretiens',
            'entretiensAujourdhui',
            'entretiensSemaine'
        ));
    }

    /**
     * Admin : uniquement les entretiens du jour, par ordre d'arrivée.
     */
    public function aujourdhui(Request $request): View
    {
        $recherche = trim((string) $request->query('recherche', ''));

        $entretiens = Entretien::query()
            ->whereDate('created_at', today())
            ->when($recherche !== '', function ($query) use ($recherche) {
                $query->where(function ($q) use ($recherche) {
                    $q->where('nom', 'like', "%{$recherche}%")
                      ->orWhere('prenoms', 'like', "%{$recherche}%")
                      ->orWhere('poste', 'like', "%{$recherche}%");
                });
            })
            ->oldest() // ordre d'arrivée : le premier arrivé en haut
            ->get();

        $totalJour = Entretien::whereDate('created_at', today())->count();
        $premiereArrivee = Entretien::whereDate('created_at', today())->oldest()->first();
        $derniereArrivee = Entretien::whereDate('created_at', today())->latest()->first();

        $dateJour = now()->locale('fr')->translatedFormat('l d F Y');

        return view('admin.entretiens-jour', compact(
            'entretiens',
            'recherche',
            'totalJour',
            'premiereArrivee',
            'derniereArrivee',
            'dateJour'
        ));
    }
}
