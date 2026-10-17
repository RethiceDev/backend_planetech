<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DevisController;
use App\Http\Controllers\DomaineController;
use App\Http\Controllers\EquipeController;
use App\Http\Controllers\PartenaireController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RealisationController;
use App\Http\Controllers\SousCategorieController;


use App\Http\Controllers\Admin\DechargeController;

use App\Http\Controllers\Admin\ProcurationController;

Route::prefix('admin')->group(function () {
    Route::get('/procurations', [ProcurationController::class, 'index'])->name('admin.procurations.index');
    Route::post('/procurations', [ProcurationController::class, 'store'])->name('admin.procurations.store');
    Route::get('/procurations/{id}/pdf', [ProcurationController::class, 'viewPdf'])->name('admin.procurations.pdf.view');
    Route::get('/procurations/{id}/download', [ProcurationController::class, 'downloadPdf'])->name('admin.procurations.pdf.download');
});

Route::prefix('admin')->group(function () {
    Route::get('/decharges', [DechargeController::class, 'index'])->name('admin.decharges.index');
    Route::post('/decharges', [DechargeController::class, 'store'])->name('admin.decharges.store');
    Route::get('/decharges/{decharge}/pdf', [DechargeController::class, 'pdf'])->name('admin.decharges.pdf');
});


    Route::get('/procuration',function(){
    return view('admin.procuration');
    });


/*
|--------------------------------------------------------------------------
| AUTHENTIFICATION ADMINISTRATEUR
|--------------------------------------------------------------------------
*/

// Page de connexion
Route::get('/Administateur', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

// Traitement de la connexion
Route::post('/Administateur/login', [AdminAuthController::class, 'login'])
    ->name('admin.login.submit');

// Déconnexion
Route::post('/Administateur/logout', [AdminAuthController::class, 'logout'])
    ->name('admin.logout');



/*
|--------------------------------------------------------------------------
| ESPACE ADMINISTRATEUR (PROTÉGÉ)
|--------------------------------------------------------------------------
|
| Toutes les routes ci-dessous nécessitent une authentification administrateur.
|
*/

Route::middleware([
    \App\Http\Middleware\AdminAuthMiddleware::class,
    \App\Http\Middleware\AdminNoCache::class,
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | TABLEAU DE BORD
    |--------------------------------------------------------------------------
    */
    Route::get('/', [AdminAuthController::class, 'dashboard'])
        ->name('admin.accueil');


    /*
    |--------------------------------------------------------------------------
    | PROFIL ADMINISTRATEUR
    |--------------------------------------------------------------------------
    */
    Route::get('/profil', [AdminAuthController::class, 'profil'])
        ->name('admin.profil');

    Route::put('/profil', [AdminAuthController::class, 'updateProfil'])
        ->name('admin.profil.update');

    Route::put('/profil/mot-de-passe', [AdminAuthController::class, 'updatePassword'])
        ->name('admin.profil.password');


    /*
    |--------------------------------------------------------------------------
    | DEMANDES DE DEVIS & PDF
    |--------------------------------------------------------------------------
    */
    Route::get('/devis', [DevisController::class, 'listes'])
        ->name('admin.devis');

    Route::get('/demandes/{demandeDevis}/voir-devis', [DevisController::class, 'voirPdf'])
        ->name('admin.devis.pdf');

    Route::get('/demandes/{demandeDevis}/creer-devis', [DevisController::class, 'creerDevis'])
        ->name('admin.devis.chiffrer');

    Route::post('/demandes/{demandeDevis}/creer-devis', [DevisController::class, 'store'])
        ->name('admin.devis.store');

    Route::delete('/demandes/{demandeDevis}', [DevisController::class, 'destroy'])
        ->name('admin.demandes.destroy');


    /*
    |--------------------------------------------------------------------------
    | RÉALISATIONS
    |--------------------------------------------------------------------------
    */
    Route::get('/realisations', [RealisationController::class, 'index'])
        ->name('realisations.index');

    Route::post('/realisations', [RealisationController::class, 'store'])
        ->name('realisations.store');

    Route::get('/realisations/{realisation}/edit', [RealisationController::class, 'edit'])
        ->name('realisations.edit');

    Route::put('/realisations/{realisation}', [RealisationController::class, 'update'])
        ->name('realisations.update');

    Route::delete('/realisations/{realisation}', [RealisationController::class, 'destroy'])
        ->name('realisations.destroy');


    /*
    |--------------------------------------------------------------------------
    | PRODUITS & BOUTIQUE
    |--------------------------------------------------------------------------
    */
    Route::resource('produits', ProductController::class)->parameters([
        'produits' => 'product',
    ]);

    Route::get('/boutique', function () {
        return redirect()->route('produits.index');
    })->name('admin.boutique');


    /*
    |--------------------------------------------------------------------------
    | CATÉGORIES & SOUS-CATÉGORIES
    |--------------------------------------------------------------------------
    */
    Route::resource('categories', CategoryController::class);

    Route::resource('sous-categories', SousCategorieController::class)->parameters([
        'sous-categories' => 'sousCategorie',
    ]);


    /*
    |--------------------------------------------------------------------------
    | PARTENAIRES, ÉQUIPE & DOMAINES
    |--------------------------------------------------------------------------
    */
    Route::resource('partenaires', PartenaireController::class);
    Route::resource('equipes', EquipeController::class);
    Route::resource('domaines', DomaineController::class);


    /*
    |--------------------------------------------------------------------------
    | COMMANDES
    |--------------------------------------------------------------------------
    */
    Route::get('/commande', [CommandeController::class, 'index'])
        ->name('admin.commandes');

    Route::post('/commande/{commande}/devis', [CommandeController::class, 'envoyerDevis'])
    ->name('commande.devis');
});
