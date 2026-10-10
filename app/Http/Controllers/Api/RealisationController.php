<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Realisation;

class RealisationController extends Controller
{
    /**
     * Liste publique des réalisations actives.
     */
    public function index()
    {
        $realisations = Realisation::query()
            ->where('actif', true)
            ->orderBy('ordre')
            ->latest()
            ->get([
                'id',
                'domaine',
                'titre',
                'description',
                'image',
                'ordre',
                'actif',
            ]);

        return response()->json([
            'success' => true,
            'data' => $realisations,
        ]);
    }
}