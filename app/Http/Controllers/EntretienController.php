<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntretienController extends Controller
{
    /**
     * Affiche la liste historique des enregistrements d'entretiens.
     */
    public function index()
    {
        $entretiens = DB::table('entretiens')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('entretiens.index', compact('entretiens'));
    }
}
