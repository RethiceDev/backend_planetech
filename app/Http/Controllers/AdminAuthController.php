<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use App\Models\DemandeDevis;
use App\Models\Product;
use App\Models\Domaine;
use App\Models\Equipe;
use App\Models\Category;
use App\Models\Commande;
use App\Models\Visit;

class AdminAuthController extends Controller
{
    /**
     * ==============================
     * CONNEXION
     * ==============================
     */

    /**
     * Afficher la page de connexion.
     */
    public function showLogin()
    {
        return view('admin.connexion');
    }


    /**
     * Traiter la connexion.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ], [
            'email.required' =>
                'Veuillez saisir votre adresse email.',

            'email.email' =>
                'Veuillez saisir une adresse email valide.',

            'password.required' =>
                'Veuillez saisir votre mot de passe.',
        ]);


        $remember = $request->boolean('remember');


        if (
            Auth::guard('admin')->attempt(
                [
                    'email' => $credentials['email'],
                    'password' => $credentials['password'],
                ],
                $remember
            )
        ) {

            $request->session()->regenerate();

            // Mémoriser l'empreinte du mot de passe pour cette session
            $request->session()->put(
                'admin_pw_hash',
                hash('sha256', (string) Auth::guard('admin')->user()->mot_de_passe)
            );


            return redirect()
                ->route('admin.accueil')
                ->with(
                    'success',
                    'Connexion réussie. Bienvenue dans votre espace administrateur.'
                );
        }


        return back()
            ->withInput(
                $request->only('email')
            )
            ->with(
                'error',
                'Adresse email ou mot de passe incorrect.'
            );
    }


    /**
     * ==============================
     * TABLEAU DE BORD
     * ==============================
     */
    public function dashboard()
    {
        $admin = Auth::guard('admin')->user();

        /*
        |--------------------------------------------------------------------------
        | DATE DU JOUR
        |--------------------------------------------------------------------------
        */

        $aujourdhui = now()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | DEMANDES DE DEVIS
        |--------------------------------------------------------------------------
        */

        $totalDevis = DemandeDevis::count();

        $devisEnAttente = DemandeDevis::where(
            'statut',
            'en_attente'
        )->count();

        $countAujourdhui = DemandeDevis::whereDate(
            'created_at',
            $aujourdhui
        )->count();

        /*
        |--------------------------------------------------------------------------
        | PRODUITS
        |--------------------------------------------------------------------------
        */

        $totalProduits = Product::count();

        $produitsSurCommande = Product::where(
            'stock',
            false
        )->count();

        $countAujourdhuipro = Product::whereDate(
            'created_at',
            $aujourdhui
        )->count();

        /*
        |--------------------------------------------------------------------------
        | CATÉGORIES
        |--------------------------------------------------------------------------
        */

        $totalCategories = Category::count();

        $countAujourdhuicat = Category::whereDate(
            'created_at',
            $aujourdhui
        )->count();

        /*
        |--------------------------------------------------------------------------
        | DOMAINES
        |--------------------------------------------------------------------------
        */

        $totalDomaines = Domaine::count();

        /*
        |--------------------------------------------------------------------------
        | ÉQUIPE
        |--------------------------------------------------------------------------
        */

        $totalEquipe = Equipe::count();

        /*
        |--------------------------------------------------------------------------
        | COMMANDES
        |--------------------------------------------------------------------------
        */

        $totalCommandes = Commande::count();

        $commandesAujourdhui = Commande::whereDate(
            'created_at',
            $aujourdhui
        )->count();

        $nouvellesCommandes = Commande::where(
            'statut',
            'recue'
        )->count();

        /*
        |--------------------------------------------------------------------------
        | DERNIÈRES COMMANDES
        |--------------------------------------------------------------------------
        */

        $dernieresCommandes = Commande::with('items')
            ->latest()
            ->take(4)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | DERNIÈRES DEMANDES DE DEVIS
        |--------------------------------------------------------------------------
        */

        $dernieresDemandes = DemandeDevis::latest()
            ->take(4)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | GRAPHIQUE 1 : ÉVOLUTION SUR LES 7 DERNIERS JOURS
        |--------------------------------------------------------------------------
        */

        $dateDebut = now()->subDays(6)->startOfDay();
        $dateFin = now()->endOfDay();

        // Préparer les 7 jours, même ceux sans activité.
        $jours = collect();

        for ($i = 0; $i < 7; $i++) {
            $date = $dateDebut->copy()->addDays($i);

            $jours->put($date->format('Y-m-d'), [
                'label' => $date->locale('fr')->translatedFormat('D d/m'),
                'commandes' => 0,
                'devis' => 0,
            ]);
        }

        // Nombre réel de commandes par jour.
        $commandesParJour = Commande::query()
            ->whereBetween('created_at', [$dateDebut, $dateFin])
            ->selectRaw('DATE(created_at) as jour, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'jour');

        // Nombre réel de demandes de devis par jour.
        $devisParJour = DemandeDevis::query()
            ->whereBetween('created_at', [$dateDebut, $dateFin])
            ->selectRaw('DATE(created_at) as jour, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'jour');

        // Transformer la collection en tableau standard pour éviter l'erreur de modification indirecte
        $joursTableau = $jours->toArray();

        // Compléter les jours avec les résultats de la base.
        foreach ($joursTableau as $date => $donnees) {
            $joursTableau[$date]['commandes'] = (int) (
                $commandesParJour[$date] ?? 0
            );

            $joursTableau[$date]['devis'] = (int) (
                $devisParJour[$date] ?? 0
            );
        }

        $jours = collect($joursTableau);

        $graphiqueEvolution = [
            'labels' => $jours->pluck('label')->values(),
            'commandes' => $jours->pluck('commandes')->values(),
            'devis' => $jours->pluck('devis')->values(),
        ];

        /*
        |--------------------------------------------------------------------------
        | GRAPHIQUE 2 : RÉPARTITION DES COMMANDES PAR STATUT
        |--------------------------------------------------------------------------
        */

        $commandesParStatut = Commande::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->orderBy('statut')
            ->get();

        $libellesStatuts = [
            'recue' => 'Nouvelle / Reçue',
            'en_cours' => 'En cours',
            'en_traitement' => 'En traitement',
            'terminee' => 'Terminée',
            'livree' => 'Livrée',
            'annulee' => 'Annulée',
            'annule' => 'Annulée',
        ];

        $graphiqueStatuts = [
            'labels' => $commandesParStatut->map(
                function ($item) use ($libellesStatuts) {
                    $statut = (string) ($item->statut ?? 'non_defini');

                    return $libellesStatuts[$statut]
                        ?? ucfirst(str_replace('_', ' ', $statut));
                }
            )->values(),

            'valeurs' => $commandesParStatut
                ->pluck('total')
                ->map(fn ($total) => (int) $total)
                ->values(),
        ];

        /*
        |--------------------------------------------------------------------------
        | STATISTIQUES DE VISITES
        |--------------------------------------------------------------------------
        */

        $statsVisites = Visit::yearEvolution();

        /*
        |--------------------------------------------------------------------------
        | ENVOYER LES DONNÉES À LA VUE
        |--------------------------------------------------------------------------
        */

        return view(
            'admin.accueil',
            compact(
                'admin',

                // Devis
                'totalDevis',
                'devisEnAttente',
                'dernieresDemandes',
                'countAujourdhui',

                // Produits
                'totalProduits',
                'produitsSurCommande',
                'countAujourdhuipro',

                // Catégories
                'totalCategories',
                'countAujourdhuicat',

                // Domaines
                'totalDomaines',

                // Équipe
                'totalEquipe',

                // Commandes
                'totalCommandes',
                'commandesAujourdhui',
                'nouvellesCommandes',
                'dernieresCommandes',

                // Graphiques dynamiques
                'graphiqueEvolution',
                'graphiqueStatuts',
                'statsVisites'
            )
        );
    }

    /**
     * ==============================
     * PROFIL ADMINISTRATEUR
     * ==============================
     */

    /**
     * Afficher le profil.
     */
    public function profil()
    {
        $admin = Auth::guard('admin')->user();

        return view(
            'admin.profil',
            compact('admin')
        );
    }


    /**
     * Modifier les informations personnelles.
     */
    public function updateProfil(Request $request)
    {
        $admin = Auth::guard('admin')->user();


        $validated = $request->validate([
            'prenom' => [
                'required',
                'string',
                'max:100',
            ],

            'nom' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:admins,email,' . $admin->id,
            ],
        ], [
            'prenom.required' =>
                'Le prénom est obligatoire.',

            'nom.required' =>
                'Le nom est obligatoire.',

            'email.required' =>
                'L’adresse email est obligatoire.',

            'email.email' =>
                'Veuillez saisir une adresse email valide.',

            'email.unique' =>
                'Cette adresse email est déjà utilisée.',
        ]);


        $admin->update($validated);


        return back()->with(
            'success',
            'Vos informations ont été mises à jour avec succès.'
        );
    }


    /**
     * Modifier le mot de passe.
     * Toutes les autres sessions de l'administrateur sont déconnectées.
     */
    public function updatePassword(Request $request)
    {
        $admin = Auth::guard('admin')->user();


        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'string',
                'min:12',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
                'confirmed',
            ],
        ], [
            'current_password.required' =>
                'Veuillez saisir votre mot de passe actuel.',

            'password.required' =>
                'Veuillez saisir un nouveau mot de passe.',

            'password.min' =>
                'Le nouveau mot de passe doit contenir au moins 12 caractères.',

            'password.regex' =>
                'Le mot de passe doit contenir des lettres, des chiffres et au moins un symbole (par exemple ! @ # $).',

            'password.confirmed' =>
                'La confirmation du mot de passe ne correspond pas.',
        ]);


        /*
        |--------------------------------------------------------------------------
        | VÉRIFIER L'ANCIEN MOT DE PASSE
        |--------------------------------------------------------------------------
        */

        if (
            !Hash::check(
                $validated['current_password'],
                $admin->mot_de_passe
            )
        ) {

            return back()
                ->withErrors([
                    'current_password' =>
                        'Votre mot de passe actuel est incorrect.',
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | ENREGISTRER LE NOUVEAU MOT DE PASSE
        |--------------------------------------------------------------------------
        */

        $admin->update([
            'mot_de_passe' => Hash::make(
                $validated['password']
            ),
        ]);

        $admin->refresh();

        // Invalide aussi les connexions "se souvenir de moi" des autres appareils
        if (Schema::hasColumn('admins', 'remember_token')) {
            $admin->setRememberToken(Str::random(60));
            $admin->save();
        }

        // Cette session reste valide : on met à jour son empreinte.
        // Toutes les autres sessions seront coupées à leur prochaine requête.
        $request->session()->put(
            'admin_pw_hash',
            hash('sha256', (string) $admin->mot_de_passe)
        );
        $request->session()->regenerate();


        return back()->with(
            'success',
            'Votre mot de passe a été modifié. Les autres appareils ont été déconnectés.'
        );
    }


    /**
     * ==============================
     * DÉCONNEXION
     * ==============================
     */

    /**
     * Déconnecter l'administrateur.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();


        $request->session()->invalidate();

        $request->session()->regenerateToken();


        return redirect()
            ->route('admin.login')
            ->with(
                'success',
                'Vous avez été déconnecté avec succès.'
            );
    }
}
