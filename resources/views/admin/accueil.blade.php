<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>PLANETECH Administration - Vue d'ensemble</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            50: '#f2f7f4',
                            700: '#1b4d3e',
                            900: '#0f2c23',
                        },
                        signal: '#1b4d3e',
                        ink: '#111827',
                        'slate-muted': '#6b7280'
                    }
                }
            }
        };
    </script>

    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Ajout de Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>


<body class="bg-gray-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    @include('admin.aside')


    {{-- MAIN --}}
    <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">

        <main class="space-y-6 p-6 lg:p-8">
                 <div class="flex items-center gap-3">

                        <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="store" class="w-5 h-5"></i>
                        </div>

                       <div>

                            <h1 class="text-2xl font-bold text-slate-900">
                                Tableau de Bord
                            </h1>

                            <p class="text-sm text-slate-muted mt-1">
                                L'essentiel, en un coup d'oeil
                            </p>

                        </div>
                 </div>

            {{-- =====================================================
                 STATISTIQUES
            ====================================================== --}}

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">


                {{-- =================================================
                     DEMANDES DE DEVIS
                ================================================== --}}

                <a
                    href="{{ route('admin.devis') }}"
                    class="group rounded-2xl bg-forest-900 p-5 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >

                    <div class="flex items-start justify-between">

                        <p class="text-sm font-medium text-emerald-100/90">
                            Demandes de devis
                        </p>

                        <div class="rounded-xl bg-white/10 p-2 text-white">

                            <i
                                data-lucide="file-text"
                                class="h-4 w-4"
                            ></i>

                        </div>

                    </div>


                    <div class="mt-4">

                        <p class="text-3xl font-bold">
                            {{ $totalDevis }}
                        </p>

                        <p class="mt-1 text-sm text-emerald-200/80">
                            {{ $countAujourdhui }} aujourd'hui
                        </p>

                    </div>

                </a>


                {{-- =================================================
                     PRODUITS
                ================================================== --}}

                <a
                    href="{{ route('produits.index') }}"
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-200 hover:shadow-md"
                >

                    <div class="flex items-start justify-between">

                        <p class="text-sm font-medium text-slate-600">
                            Produits en boutique
                        </p>

                        <div class="rounded-xl bg-forest-50 p-2 text-forest-700">

                            <i
                                data-lucide="shopping-bag"
                                class="h-4 w-4"
                            ></i>

                        </div>

                    </div>


                    <div class="mt-4">

                        <p class="text-3xl font-bold text-slate-900">
                            {{ $totalProduits }}
                        </p>

                        <p class="mt-1 text-sm text-slate-muted">
                            {{ $countAujourdhuipro }} enregistré(s) aujourd'hui
                        </p>

                    </div>

                </a>


                {{-- =================================================
                     CATÉGORIES
                ================================================== --}}

                <a
                    href="{{ route('categories.index') }}"
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-200 hover:shadow-md"
                >

                    <div class="flex items-start justify-between">

                        <p class="text-sm font-medium text-slate-600">
                            Catégories de produits
                        </p>

                        <div class="rounded-xl bg-forest-50 p-2 text-forest-700">

                            <i
                                data-lucide="tags"
                                class="h-4 w-4"
                            ></i>

                        </div>

                    </div>


                    <div class="mt-4">

                        <p class="text-3xl font-bold text-slate-900">
                            {{ $totalCategories }}
                        </p>

                        <p class="mt-1 text-sm text-slate-muted">
                            {{ $countAujourdhuicat }} enregistrée(s) aujourd'hui
                        </p>

                    </div>

                </a>


                {{-- =================================================
                     COMMANDES
                ================================================== --}}

                <a
                    href="{{ route('admin.commandes') }}"
                    class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md"
                >

                    <div class="flex items-start justify-between">

                        <p class="text-sm font-medium text-slate-600">
                            Commandes
                        </p>

                        <div class="rounded-xl bg-emerald-50 p-2 text-emerald-700 transition group-hover:bg-emerald-100">

                            <i
                                data-lucide="shopping-cart"
                                class="h-4 w-4"
                            ></i>

                        </div>

                    </div>


                    <div class="mt-4">

                        <p class="text-3xl font-bold text-slate-900">
                            {{ $totalCommandes }}
                        </p>

                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">

                            <span class="text-slate-muted">
                                {{ $commandesAujourdhui }} aujourd'hui
                            </span>

                            @if($nouvellesCommandes > 0)

                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                    {{ $nouvellesCommandes }} à traiter
                                </span>

                            @else

                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                    À jour
                                </span>

                            @endif

                        </div>

                    </div>

                </a>

            </div>

            {{-- =====================================================
     STATISTIQUES DE VISITES
====================================================== --}}
@isset($statsVisites)
@php
    $sv = $statsVisites;
    $pct = $sv['evolution'];
    $hausse = $pct !== null && $pct >= 0;
@endphp

<div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm border-l-4 border-l-forest-700">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-forest-50 text-forest-700 flex items-center justify-center">
                <i data-lucide="eye" class="h-5 w-5"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-muted">Visiteurs du site</p>
                <p class="text-xl font-bold text-slate-900">
                    {{ $sv['moisLabel'] }} : {{ number_format($sv['moisTotal'], 0, ',', ' ') }}
                    visite{{ $sv['moisTotal'] > 1 ? 's' : '' }}
                    @if($pct !== null)
                        <span class="text-sm font-semibold {{ $hausse ? 'text-emerald-600' : 'text-red-600' }}">
                            ({{ $hausse ? '+' : '' }}{{ $pct }}% vs {{ strtolower($sv['precedentLabel']) }})
                        </span>
                    @else
                        <span class="text-sm font-medium text-slate-muted">
                            (pas de données en {{ strtolower($sv['precedentLabel']) }})
                        </span>
                    @endif
                </p>
            </div>
        </div>

        @if(count($sv['sources']) > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($sv['sources'] as $s)
                    <span class="rounded-full bg-forest-50 px-3 py-1 text-xs font-semibold text-forest-700">
                        {{ $s['source'] }} : {{ $s['total'] }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-900">Évolution {{ $sv['year'] }}</h3>
        <p class="mt-1 text-xs text-slate-muted">Total des visites par mois (mois terminés)</p>
        <div class="mt-6 relative h-64 w-full">
            <canvas id="visitsYearChart"></canvas>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-900">{{ $sv['moisLabel'] }} : jour par jour</h3>
        <p class="mt-1 text-xs text-slate-muted">Visites enregistrées depuis le 1er du mois</p>
        <div class="mt-6 relative h-64 w-full">
            <canvas id="visitsMonthChart"></canvas>
        </div>
    </div>
</div>
@endisset

            {{-- =====================================================
                 SECTION GRAPHIQUES (ÉVOLUTION & RÉPARTITION)
            ====================================================== --}}

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Graphique d'évolution (Courbe) --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-2 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">
                                Évolution de l'activité
                            </h3>
                            <span class="text-xs font-medium text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                                7 derniers jours
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-slate-muted">
                            Suivi du volume des commandes et des demandes de devis enregistrées.
                        </p>
                    </div>

                    <div class="mt-6 relative h-72 w-full">
                        <canvas id="evolutionChart"></canvas>
                    </div>
                </div>

                {{-- Graphique de répartition (Anneau) --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">
                                Statut des commandes
                            </h3>
                            <i data-lucide="pie-chart" class="h-4 w-4 text-slate-400"></i>
                        </div>
                        <p class="mt-1 text-xs text-slate-muted">
                            Répartition globale par état de traitement.
                        </p>
                    </div>

                    <div class="mt-6 relative h-64 w-full flex items-center justify-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

            </div>


            {{-- =====================================================
                 DERNIÈRES ACTIVITÉS
            ====================================================== --}}

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">


                {{-- =================================================
                     DERNIÈRES DEMANDES
                ================================================== --}}

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-2">

                    <div class="mb-1 flex items-center justify-between">

                        <h3 class="text-base font-bold text-slate-900">
                            Dernières demandes
                        </h3>

                        <a
                            href="{{ route('admin.devis') }}"
                            class="flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline"
                        >

                            Tout voir

                            <i
                                data-lucide="arrow-up-right"
                                class="h-4 w-4"
                            ></i>

                        </a>

                    </div>


                    <p class="mb-6 text-xs text-slate-muted">
                        Les quatre demandes les plus récentes
                    </p>


                    <div class="space-y-3">

                        @forelse($dernieresDemandes as $devis)

                            @php

                                $statutDevisClasses = match($devis->statut) {

                                    'en_attente' =>
                                        'bg-amber-50 text-amber-700',

                                    'chiffree' =>
                                        'bg-blue-50 text-blue-700',

                                    'envoye' =>
                                        'bg-green-50 text-green-700',

                                    default =>
                                        'bg-gray-100 text-gray-600',

                                };


                                $statutDevisLabel = match($devis->statut) {

                                    'en_attente' =>
                                        'En attente',

                                    'chiffree' =>
                                        'Chiffrée',

                                    'envoye' =>
                                        'Envoyée',

                                    default =>
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $devis->statut
                                            )
                                        ),

                                };

                            @endphp


                            <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-100 p-4 transition hover:bg-gray-50">

                                <div class="flex min-w-0 items-center gap-3">

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-forest-50 text-forest-700">

                                        <i
                                            data-lucide="file-text"
                                            class="h-5 w-5"
                                        ></i>

                                    </div>


                                    <div class="min-w-0">

                                        <h4 class="truncate text-sm font-semibold text-slate-900">

                                            {{
                                                $devis->nom
                                                    ?: $devis->raison_sociale
                                                    ?: 'Client'
                                            }}

                                        </h4>

                                        <p class="mt-0.5 truncate text-xs text-slate-muted">
                                            {{ $devis->email }}
                                        </p>

                                        <p class="mt-1 text-[11px] text-slate-400">

                                            {{
                                                $devis->created_at
                                                    ? $devis->created_at->format('d/m/Y à H:i')
                                                    : '-'
                                            }}

                                        </p>

                                    </div>

                                </div>


                                <span
                                    class="shrink-0 rounded-full px-3 py-1 text-[10px] font-semibold {{ $statutDevisClasses }}"
                                >
                                    {{ $statutDevisLabel }}
                                </span>

                            </div>

                        @empty

                            <div class="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">

                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-forest-50 text-forest-700">

                                    <i
                                        data-lucide="inbox"
                                        class="h-6 w-6"
                                    ></i>

                                </div>

                                <h4 class="text-sm font-bold text-slate-900">
                                    Aucune demande pour l'instant
                                </h4>

                                <p class="mt-1 text-xs text-slate-muted">
                                    Les demandes envoyées depuis le site s'afficheront ici.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>


                {{-- =================================================
                     DERNIÈRES COMMANDES
                ================================================== --}}

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">

                    <div class="mb-1 flex items-center justify-between">

                        <h3 class="text-base font-bold text-slate-900">
                            Dernières commandes
                        </h3>

                        <a
                            href="{{ route('admin.commandes') }}"
                            class="flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline"
                        >

                            Tout voir

                            <i
                                data-lucide="arrow-up-right"
                                class="h-4 w-4"
                            ></i>

                        </a>

                    </div>


                    <p class="mb-5 text-xs text-slate-muted">
                        Les quatre commandes les plus récentes
                    </p>


                    <div class="space-y-3">

                        @forelse($dernieresCommandes as $commande)

                            @php

                                $nomClientCommande =
                                    $commande->type_client === 'entreprise'
                                    && $commande->raison_sociale
                                        ? $commande->raison_sociale
                                        : $commande->nom;


                                $statutCommandeClasses = match($commande->statut) {

                                    'recue' =>
                                        'bg-blue-50 text-blue-700',

                                    'en_cours',
                                    'en_traitement' =>
                                        'bg-amber-50 text-amber-700',

                                    'terminee',
                                    'livree' =>
                                        'bg-emerald-50 text-emerald-700',

                                    default =>
                                        'bg-gray-100 text-gray-600',

                                };


                                $statutCommandeLabel = match($commande->statut) {

                                    'recue' =>
                                        'Nouvelle',

                                    'en_cours' =>
                                        'En cours',

                                    'en_traitement' =>
                                        'En traitement',

                                    'terminee' =>
                                        'Terminée',

                                    'livree' =>
                                        'Livrée',

                                    default =>
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $commande->statut
                                            )
                                        ),

                                };

                            @endphp


                            <a
                                href="{{ route('admin.commandes') }}"
                                class="block rounded-xl border border-gray-100 p-4 transition hover:bg-gray-50 hover:border-emerald-100"
                            >

                                <div class="flex items-start justify-between gap-3">

                                    <div class="flex min-w-0 items-center gap-3">

                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">

                                            <i
                                                data-lucide="shopping-cart"
                                                class="h-5 w-5"
                                            ></i>

                                        </div>


                                        <div class="min-w-0">

                                            <p class="truncate text-xs font-bold text-slate-900">
                                                {{ $commande->reference }}
                                            </p>

                                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                                {{ $nomClientCommande }}
                                            </p>

                                            <p class="mt-1 text-[10px] text-slate-400">

                                                {{
                                                    $commande->created_at
                                                        ? $commande->created_at->format('d/m/Y à H:i')
                                                        : '-'
                                                }}

                                            </p>

                                        </div>

                                    </div>


                                    <span
                                        class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $statutCommandeClasses }}"
                                    >
                                        {{ $statutCommandeLabel }}
                                    </span>

                                </div>


                                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">

                                    <span class="text-[10px] text-slate-400">

                                        {{ $commande->items->sum('quantite') }}
                                        unité(s)

                                    </span>


                                    <span class="text-xs font-bold text-slate-900">

                                        {{
                                            number_format(
                                                (float) $commande->total,
                                                0,
                                                ',',
                                                ' '
                                            )
                                        }}

                                        FCFA

                                    </span>

                                </div>

                            </a>

                        @empty

                            <div class="rounded-2xl border-2 border-dashed border-gray-200 p-10 text-center">

                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">

                                    <i
                                        data-lucide="shopping-cart"
                                        class="h-6 w-6"
                                    ></i>

                                </div>

                                <h4 class="text-sm font-bold text-slate-900">
                                    Aucune commande
                                </h4>

                                <p class="mt-1 text-xs text-slate-muted">
                                    Les commandes reçues depuis la boutique apparaîtront ici.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 RACCOURCIS
            ====================================================== --}}

            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">

                <div class="mb-4">

                    <h3 class="text-base font-bold text-slate-900">
                        Raccourcis
                    </h3>

                    <p class="mt-1 text-xs text-slate-muted">
                        Accès rapide aux principales fonctions de l'administration.
                    </p>

                </div>


                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                    {{-- DEVIS --}}
                    <a
                        href="{{ route('admin.devis') }}"
                        class="group flex items-center justify-between rounded-xl border border-gray-100 p-4 transition hover:border-forest-700/30 hover:bg-forest-50/30"
                    >

                        <div>

                            <h4 class="text-sm font-semibold text-slate-900 group-hover:text-forest-700">
                                Traiter les devis
                            </h4>

                            <p class="mt-0.5 text-xs text-slate-muted">
                                Qualifier les demandes reçues
                            </p>

                        </div>

                        <i
                            data-lucide="arrow-up-right"
                            class="h-4 w-4 text-slate-400 group-hover:text-forest-700"
                        ></i>

                    </a>


                    {{-- PRODUITS --}}
                    <a
                        href="{{ route('produits.index') }}"
                        class="group flex items-center justify-between rounded-xl border border-gray-100 p-4 transition hover:border-forest-700/30 hover:bg-forest-50/30"
                    >

                        <div>

                            <h4 class="text-sm font-semibold text-slate-900 group-hover:text-forest-700">
                                Ajouter un produit
                            </h4>

                            <p class="mt-0.5 text-xs text-slate-muted">
                                Enrichir le catalogue de la boutique
                            </p>

                        </div>

                        <i
                            data-lucide="arrow-up-right"
                            class="h-4 w-4 text-slate-400 group-hover:text-forest-700"
                        ></i>

                    </a>


                    {{-- CATÉGORIES --}}
                    <a
                        href="{{ route('categories.index') }}"
                        class="group flex items-center justify-between rounded-xl border border-gray-100 p-4 transition hover:border-forest-700/30 hover:bg-forest-50/30"
                    >

                        <div>

                            <h4 class="text-sm font-semibold text-slate-900 group-hover:text-forest-700">
                                Gérer les catégories
                            </h4>

                            <p class="mt-0.5 text-xs text-slate-muted">
                                Organiser le catalogue
                            </p>

                        </div>

                        <i
                            data-lucide="arrow-up-right"
                            class="h-4 w-4 text-slate-400 group-hover:text-forest-700"
                        ></i>

                    </a>

                </div>

            </div>

        </main>

    </div>

</div>




@php
    $defautEvolution = (object) [
        'labels' => [],
        'commandes' => [],
        'devis' => [],
    ];

    $defautStatuts = (object) [
        'labels' => [],
        'valeurs' => [],
    ];
@endphp

<script>


    /*
    |--------------------------------------------------------------------------
    | STATISTIQUES DE VISITES
    |--------------------------------------------------------------------------
    */
    const visites = @json($statsVisites ?? null);

    if (visites && typeof Chart !== 'undefined') {
        const optionsVisites = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f2c23',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function (c) { return ' ' + c.parsed.y + ' visite(s)'; }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } }, border: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b', padding: 8, font: { size: 11 } }, grid: { color: '#f1f5f9' }, border: { display: false } }
            }
        };

        const canvasAnnee = document.getElementById('visitsYearChart');
        if (canvasAnnee) {
            new Chart(canvasAnnee, {
                type: 'line',
                data: {
                    labels: visites.monthLabels,
                    datasets: [{
                        data: visites.monthTotals,
                        borderColor: '#1b4d3e',
                        backgroundColor: 'rgba(27, 77, 62, 0.10)',
                        borderWidth: 3, tension: 0.35, fill: true,
                        pointBackgroundColor: '#1b4d3e', pointBorderColor: '#fff',
                        pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6
                    }]
                },
                options: optionsVisites
            });
        }

        const canvasMois = document.getElementById('visitsMonthChart');
        if (canvasMois) {
            new Chart(canvasMois, {
                type: 'line',
                data: {
                    labels: visites.dayLabels,
                    datasets: [{
                        data: visites.dayTotals,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        borderWidth: 2, tension: 0.35, fill: true,
                        pointBackgroundColor: '#10b981', pointBorderColor: '#fff',
                        pointBorderWidth: 2, pointRadius: 3, pointHoverRadius: 5
                    }]
                },
                options: optionsVisites
            });
        }
    }

    
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide) {
        window.lucide.createIcons();
    }

    /*
    |--------------------------------------------------------------------------
    | DONNÉES TRANSMISES PAR LE CONTRÔLEUR LARAVEL
    |--------------------------------------------------------------------------
    */

    const evolution = @json($graphiqueEvolution ?? $defautEvolution);
    const statuts = @json($graphiqueStatuts ?? $defautStatuts);

    /*
    |--------------------------------------------------------------------------
    | GRAPHIQUE 1 : ÉVOLUTION DES COMMANDES ET DES DEVIS
    |--------------------------------------------------------------------------
    */

    const evolutionCanvas = document.getElementById('evolutionChart');

    if (evolutionCanvas && typeof Chart !== 'undefined') {
        new Chart(evolutionCanvas, {
            type: 'line',
            data: {
                labels: evolution.labels || [],
                datasets: [
                    {
                        label: 'Commandes',
                        data: evolution.commandes || [],
                        borderColor: '#1b4d3e',
                        backgroundColor: 'rgba(27, 77, 62, 0.10)',
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#1b4d3e',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        spanGaps: true
                    },
                    {
                        label: 'Demandes de devis',
                        data: evolution.devis || [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        borderWidth: 2,
                        borderDash: [6, 4],
                        tension: 0.35,
                        fill: false,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        spanGaps: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 18,
                            font: {
                                family: 'Arial, sans-serif',
                                size: 11
                            },
                            color: '#475569'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f2c23',
                        titleColor: '#ffffff',
                        bodyColor: '#f0fdf4',
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ' : ' +
                                    context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11
                            }
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1,
                            color: '#64748b',
                            padding: 8,
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        border: {
                            display: false,
                            dash: [4, 4]
                        }
                    }
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | GRAPHIQUE 2 : RÉPARTITION DES COMMANDES PAR STATUT
    |--------------------------------------------------------------------------
    */

    const statusCanvas = document.getElementById('statusChart');

    if (statusCanvas && typeof Chart !== 'undefined') {
        const labelsStatuts = statuts.labels || [];

        const valeursStatuts = (statuts.valeurs || []).map(function (valeur) {
            return Number(valeur) || 0;
        });

        const totalStatuts = valeursStatuts.reduce(function (total, valeur) {
            return total + valeur;
        }, 0);

        const couleursStatuts = [
            '#3b82f6',
            '#f59e0b',
            '#10b981',
            '#6366f1',
            '#14b8a6',
            '#ef4444',
            '#8b5cf6',
            '#64748b'
        ];

        const centreTotalPlugin = {
            id: 'centreTotalCommandes',
            afterDraw: function (chart) {
                const ctx = chart.ctx;
                const chartArea = chart.chartArea;

                if (!chartArea) {
                    return;
                }

                const centreX = (chartArea.left + chartArea.right) / 2;
                const centreY = (chartArea.top + chartArea.bottom) / 2;

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                ctx.font = 'bold 27px Arial';
                ctx.fillStyle = '#0f172a';
                ctx.fillText(
                    totalStatuts.toLocaleString('fr-FR'),
                    centreX,
                    centreY - 7
                );

                ctx.font = '11px Arial';
                ctx.fillStyle = '#64748b';
                ctx.fillText('Commandes', centreX, centreY + 17);

                ctx.restore();
            }
        };

        new Chart(statusCanvas, {
            type: 'doughnut',
            plugins: [centreTotalPlugin],
            data: {
                labels: labelsStatuts,
                datasets: [
                    {
                        label: 'Commandes',
                        data: valeursStatuts,
                        backgroundColor: couleursStatuts.slice(
                            0,
                            valeursStatuts.length
                        ),
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 7,
                        borderRadius: 4,
                        spacing: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            padding: 14,
                            color: '#475569',
                            font: {
                                family: 'Arial, sans-serif',
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f2c23',
                        titleColor: '#ffffff',
                        bodyColor: '#f0fdf4',
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function (context) {
                                const valeur = context.parsed;
                                const pourcentage = totalStatuts > 0
                                    ? ((valeur / totalStatuts) * 100).toFixed(1)
                                    : '0.0';

                                return ' ' + context.label + ' : ' +
                                    valeur + ' (' + pourcentage + ' %)';
                            }
                        }
                    }
                }
            }
        });

        if (totalStatuts === 0) {
            const conteneur = statusCanvas.parentElement;
            const message = document.createElement('p');

            message.className =
                'absolute inset-x-0 bottom-1 text-center text-xs text-slate-400';

            message.textContent = 'Aucune commande enregistrée';
            conteneur.appendChild(message);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALISATION AUTOMATIQUE TOUTES LES 60 SECONDES
    |--------------------------------------------------------------------------
    */

    window.setInterval(function () {
        if (!document.hidden) {
            window.location.reload();
        }
    }, 60000);
});
</script>

</body>
</html>
