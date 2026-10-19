<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DevisController;
use App\Http\Controllers\Api\DomainesController;
use App\Http\Controllers\Api\PartenaireController;
use App\Http\Controllers\Api\EquipeController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\RealisationController as ApiRealisationController;
use App\Http\Controllers\VisitController;

Route::post('/track', [VisitController::class, 'track'])->middleware('throttle:120,1');
Route::post('/devis', [DevisController::class, 'store']);

// Produits : liste paginée/filtrée + détail
Route::get('/produits', [ProduitController::class, 'listes']);
Route::get('/produits/{product}', [ProduitController::class, 'show']);

// Catégories + sous-catégories (chargement global)
Route::get('/categories', [CategoryController::class, 'listes']);
Route::get('/categorie', [CategoryController::class, 'listes']); // alias

Route::get('/domaines', [DomainesController::class, 'listes']);

Route::get('/partenaires', [PartenaireController::class, 'listes']);

Route::get('/equipe', [EquipeController::class, 'listes']);

Route::post('/commandes', [CommandeController::class, 'store']);

Route::get('/realisations', [ApiRealisationController::class, 'index']);
