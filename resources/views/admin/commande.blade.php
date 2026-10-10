<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>PLANETECH Administration - Commandes</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            50: '#f2f7f4',
                            100: '#e2eee8',
                            700: '#1b4d3e',
                            800: '#15523f',
                            900: '#0f2c23',
                        }
                    }
                }
            }
        };
    </script>

</head>


<body class="bg-gray-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">

    @include('admin.aside')

    <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">

        <main class="space-y-6 p-6 lg:p-8">

            {{-- =========================================================
                 HEADER
            ========================================================== --}}



                <div class="flex items-center gap-3">

                        <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                        </div>

                      <div>
                            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                                Commandes
                            </h1>

                            <p class="mt-1 text-sm text-slate-500">
                                Consultez et suivez les commandes reçues depuis la boutique.
                            </p>

                        </div>
                </div>

            {{-- =========================================================
                 STATISTIQUES
            ========================================================== --}}

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                {{-- TOTAL --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs text-slate-400">
                                Total commandes
                            </p>

                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $totalCommandes }}
                            </p>

                            <p class="mt-1 text-[10px] text-slate-400">
                                Toutes les commandes
                            </p>

                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">

                            <i
                                data-lucide="shopping-bag"
                                class="h-5 w-5"
                            ></i>

                        </div>

                    </div>

                </div>


                {{-- NOUVELLES --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs text-slate-400">
                                Nouvelles
                            </p>

                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $nouvellesCommandes }}
                            </p>

                            <p class="mt-1 text-[10px] text-blue-500">
                                À traiter
                            </p>

                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                            <i
                                data-lucide="clock-3"
                                class="h-5 w-5"
                            ></i>

                        </div>

                    </div>

                </div>


                {{-- EN COURS --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs text-slate-400">
                                En cours
                            </p>

                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $commandesEnCours }}
                            </p>

                            <p class="mt-1 text-[10px] text-amber-600">
                                En traitement
                            </p>

                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">

                            <i
                                data-lucide="loader-circle"
                                class="h-5 w-5"
                            ></i>

                        </div>

                    </div>

                </div>


                {{-- TERMINÉES --}}
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs text-slate-400">
                                Terminées
                            </p>

                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $commandesTerminees }}
                            </p>

                            <p class="mt-1 text-[10px] text-emerald-600">
                                Commandes terminées
                            </p>

                        </div>

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">

                            <i
                                data-lucide="check-circle-2"
                                class="h-5 w-5"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =========================================================
                 FILTRES
            ========================================================== --}}

            <form
                id="filtresCommandes"
                method="GET"
                action="{{ url()->current() }}"
                class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
            >

                <input
                    type="hidden"
                    name="statut"
                    id="statutFiltre"
                    value="{{ $statut }}"
                >

                <div class="flex flex-col gap-4 xl:flex-row xl:items-center">

                    <div class="flex flex-1 flex-wrap items-center gap-2">

                        {{-- TOUTES --}}
                        <button
                            type="button"
                            class="filtre-statut rounded-xl px-4 py-2 text-xs font-semibold transition
                            {{ $statut === 'toutes'
                                ? 'bg-emerald-700 text-white'
                                : 'border border-gray-200 bg-white text-slate-600 hover:bg-gray-50'
                            }}"
                            data-statut="toutes"
                        >
                            Toutes

                            <span class="ml-1 opacity-70">
                                {{ $totalCommandes }}
                            </span>

                        </button>


                        {{-- NOUVELLES --}}
                        <button
                            type="button"
                            class="filtre-statut rounded-xl px-4 py-2 text-xs font-semibold transition
                            {{ $statut === 'nouvelles'
                                ? 'bg-emerald-700 text-white'
                                : 'border border-gray-200 bg-white text-slate-600 hover:bg-gray-50'
                            }}"
                            data-statut="nouvelles"
                        >
                            Nouvelles

                            <span class="ml-1 opacity-70">
                                {{ $nouvellesCommandes }}
                            </span>

                        </button>


                        {{-- EN COURS --}}
                        <button
                            type="button"
                            class="filtre-statut rounded-xl px-4 py-2 text-xs font-semibold transition
                            {{ $statut === 'en_cours'
                                ? 'bg-emerald-700 text-white'
                                : 'border border-gray-200 bg-white text-slate-600 hover:bg-gray-50'
                            }}"
                            data-statut="en_cours"
                        >
                            En cours

                            <span class="ml-1 opacity-70">
                                {{ $commandesEnCours }}
                            </span>

                        </button>


                        {{-- TERMINÉES --}}
                        <button
                            type="button"
                            class="filtre-statut rounded-xl px-4 py-2 text-xs font-semibold transition
                            {{ $statut === 'terminees'
                                ? 'bg-emerald-700 text-white'
                                : 'border border-gray-200 bg-white text-slate-600 hover:bg-gray-50'
                            }}"
                            data-statut="terminees"
                        >
                            Terminées

                            <span class="ml-1 opacity-70">
                                {{ $commandesTerminees }}
                            </span>

                        </button>

                    </div>


                    {{-- RECHERCHE --}}
                    <div class="relative w-full xl:w-80">

                        <i
                            data-lucide="search"
                            class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        ></i>

                        <input
                            id="rechercheCommandes"
                            name="recherche"
                            type="text"
                            value="{{ $recherche }}"
                            placeholder="N° commande, client, email..."
                            autocomplete="off"
                            class="w-full rounded-xl border border-gray-200 py-2.5 pl-10 pr-4 text-xs outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                        >

                    </div>

                </div>

            </form>


            {{-- =========================================================
                 TABLEAU
            ========================================================== --}}

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">

                <div class="flex flex-col gap-2 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h3 class="text-sm font-bold text-slate-900">
                            Commandes reçues
                        </h3>

                        <p class="mt-1 text-xs text-slate-400">

                            @if($commandes->total() > 0)

                                Affichage de
                                <span class="font-semibold text-slate-600">
                                    {{ $commandes->firstItem() }}
                                </span>
                                à
                                <span class="font-semibold text-slate-600">
                                    {{ $commandes->lastItem() }}
                                </span>
                                sur
                                <span class="font-semibold text-slate-600">
                                    {{ $commandes->total() }}
                                </span>
                                commande{{ $commandes->total() > 1 ? 's' : '' }}

                            @else

                                Aucune commande trouvée

                            @endif

                        </p>

                    </div>


                    <div class="inline-flex w-fit items-center gap-2 rounded-full bg-gray-50 px-3 py-1.5 text-[10px] font-semibold text-slate-500">

                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                        @switch($statut)

                            @case('nouvelles')
                                Nouvelles commandes
                                @break

                            @case('en_cours')
                                Commandes en cours
                                @break

                            @case('terminees')
                                Commandes terminées
                                @break

                            @default
                                Toutes les commandes

                        @endswitch

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1100px]">

                        <thead>

                            <tr class="border-b border-gray-100 bg-gray-50">

                                <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Commande
                                </th>

                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Client
                                </th>

                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Articles
                                </th>

                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Montant
                                </th>

                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Statut
                                </th>

                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Date
                                </th>

                                <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                        @forelse($commandes as $commande)

                            @php

                                $nomClient =
                                    $commande->type_client === 'entreprise'
                                    && $commande->raison_sociale
                                        ? $commande->raison_sociale
                                        : $commande->nom;

                                $initiales = collect(
                                    preg_split(
                                        '/\s+/',
                                        trim($nomClient)
                                    )
                                )
                                ->filter()
                                ->take(2)
                                ->map(
                                    fn ($mot) => strtoupper(
                                        mb_substr($mot, 0, 1)
                                    )
                                )
                                ->implode('');

                                $statutLabel = match ($commande->statut) {

                                    'recue' => 'Nouvelle',

                                    'en_cours' => 'En cours',

                                    'en_traitement' => 'En traitement',

                                    'terminee' => 'Terminée',

                                    'livree' => 'Livrée',

                                    default => ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $commande->statut
                                        )
                                    ),

                                };

                                $statutClasses = match ($commande->statut) {

                                    'recue' =>
                                        'bg-blue-50 text-blue-700',

                                    'en_cours',
                                    'en_traitement' =>
                                        'bg-amber-50 text-amber-700',

                                    'terminee',
                                    'livree' =>
                                        'bg-emerald-50 text-emerald-700',

                                    default =>
                                        'bg-gray-100 text-gray-700',

                                };

                                $nombreArticles = $commande->items->sum('quantite');

                            @endphp


                            <tr
                                class="transition hover:bg-gray-50"
                            >

                                {{-- COMMANDE --}}
                                <td class="px-6 py-4">

                                    <p class="text-xs font-bold text-slate-900">
                                        {{ $commande->reference }}
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        #{{ $commande->id }}
                                    </p>

                                </td>


                                {{-- CLIENT --}}
                                <td class="px-4 py-4">

                                    <div class="flex items-center gap-3">

                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-700">
                                            {{ $initiales ?: '?' }}
                                        </div>

                                        <div class="min-w-0">

                                            <p class="max-w-[220px] truncate text-xs font-bold text-slate-900">
                                                {{ $nomClient }}
                                            </p>

                                            @if(
                                                $commande->type_client === 'entreprise'
                                                && $commande->raison_sociale
                                            )

                                                <p class="mt-0.5 text-[10px] text-slate-400">
                                                    Contact : {{ $commande->nom }}
                                                </p>

                                            @endif

                                            <p class="max-w-[220px] truncate text-[10px] text-slate-400">
                                                {{ $commande->email }}
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                {{-- ARTICLES --}}
                                <td class="px-4 py-4">

                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ $commande->items->count() }} article(s)
                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $nombreArticles }} unité(s)
                                    </p>

                                </td>


                                {{-- MONTANT --}}
                                <td class="px-4 py-4">

                                    <p class="text-sm font-bold text-slate-900">

                                        {{
                                            number_format(
                                                (float) $commande->total,
                                                0,
                                                ',',
                                                ' '
                                            )
                                        }}

                                        FCFA

                                    </p>

                                </td>


                                {{-- STATUT --}}
                                <td class="px-4 py-4">

                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[10px] font-semibold {{ $statutClasses }}"
                                    >

                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                                        {{ $statutLabel }}

                                    </span>

                                </td>


                                {{-- DATE --}}
                                <td class="px-4 py-4">

                                    <p class="text-xs font-medium text-slate-700">

                                        {{
                                            $commande->created_at
                                                ? $commande->created_at->format('d/m/Y')
                                                : '-'
                                        }}

                                    </p>

                                    <p class="mt-1 text-[10px] text-slate-400">

                                        {{
                                            $commande->created_at
                                                ? $commande->created_at->format('H:i')
                                                : '-'
                                        }}

                                    </p>

                                </td>


                                {{-- ACTION --}}
                                <td class="px-6 py-4 text-right">

                                    <button
                                        type="button"
                                        onclick="ouvrirCommande({{ $commande->id }})"
                                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800"
                                    >

                                        <i
                                            data-lucide="eye"
                                            class="h-4 w-4"
                                        ></i>

                                        Voir

                                    </button>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="7"
                                    class="px-6 py-16 text-center"
                                >

                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">

                                        <i
                                            data-lucide="shopping-bag"
                                            class="h-6 w-6 text-slate-400"
                                        ></i>

                                    </div>

                                    <p class="mt-4 text-sm font-semibold text-slate-700">
                                        Aucune commande
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Aucune commande ne correspond à vos critères.
                                    </p>

                                    @if($recherche || $statut !== 'toutes')

                                        <a
                                            href="{{ url()->current() }}"
                                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800"
                                        >

                                            <i
                                                data-lucide="rotate-ccw"
                                                class="h-4 w-4"
                                            ></i>

                                            Réinitialiser les filtres

                                        </a>

                                    @endif

                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- =====================================================
                     PAGINATION
                ====================================================== --}}

                @if($commandes->hasPages())

                    <div class="border-t border-gray-100 bg-white px-6 py-4">

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                            {{-- INFORMATIONS --}}
                            <div class="text-xs text-slate-500">

                                Affichage de

                                <span class="font-semibold text-slate-700">
                                    {{ $commandes->firstItem() }}
                                </span>

                                à

                                <span class="font-semibold text-slate-700">
                                    {{ $commandes->lastItem() }}
                                </span>

                                sur

                                <span class="font-semibold text-slate-700">
                                    {{ $commandes->total() }}
                                </span>

                                commande{{ $commandes->total() > 1 ? 's' : '' }}

                            </div>


                            {{-- NAVIGATION --}}
                            <div class="flex items-center gap-2">

                                {{-- PRÉCÉDENT --}}
                                @if($commandes->onFirstPage())

                                    <span
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400"
                                    >
                                        Précédent
                                    </span>

                                @else

                                    <a
                                        href="{{ $commandes->previousPageUrl() }}"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >

                                        <i
                                            data-lucide="chevron-left"
                                            class="mr-1 h-4 w-4"
                                        ></i>

                                        Précédent

                                    </a>

                                @endif


                                {{-- NUMÉROS --}}
                                <div class="hidden items-center gap-1 sm:flex">

                                    @foreach(
                                        $commandes->getUrlRange(
                                            max(1, $commandes->currentPage() - 2),
                                            min($commandes->lastPage(), $commandes->currentPage() + 2)
                                        ) as $page => $url
                                    )

                                        @if($page == $commandes->currentPage())

                                            <span
                                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-emerald-700 px-3 text-xs font-bold text-white shadow-sm"
                                            >
                                                {{ $page }}
                                            </span>

                                        @else

                                            <a
                                                href="{{ $url }}"
                                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                {{ $page }}
                                            </a>

                                        @endif

                                    @endforeach

                                </div>


                                {{-- MOBILE --}}
                                <span
                                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-slate-100 px-3 text-xs font-bold text-slate-700 sm:hidden"
                                >
                                    {{ $commandes->currentPage() }}
                                </span>


                                {{-- SUIVANT --}}
                                @if($commandes->hasMorePages())

                                    <a
                                        href="{{ $commandes->nextPageUrl() }}"
                                        class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800"
                                    >

                                        Suivant

                                        <i
                                            data-lucide="chevron-right"
                                            class="ml-1 h-4 w-4"
                                        ></i>

                                    </a>

                                @else

                                    <span
                                        class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-400"
                                    >
                                        Suivant
                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </main>

    </div>

</div>


{{-- =============================================================
     MODAL COMMANDE
============================================================= --}}

<div
    id="commandeModal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>

    {{-- OVERLAY --}}
    <div
        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
        onclick="fermerCommande()"
    ></div>


    {{-- CONTENEUR --}}
    <div class="relative flex min-h-full items-center justify-center p-3 sm:p-4">

        <div
            class="relative flex h-[92vh] w-full max-w-5xl min-h-0 flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
        >

            {{-- HEADER --}}
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-6 py-5">

                <div class="min-w-0">

                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">
                        Détail de la commande
                    </p>

                    <h2
                        id="modalReference"
                        class="mt-1 text-xl font-bold text-slate-900"
                    >
                        -
                    </h2>

                    <p
                        id="modalCommandeId"
                        class="mt-1 text-xs text-slate-400"
                    >
                        -
                    </p>

                </div>

                <button
                    type="button"
                    onclick="fermerCommande()"
                    class="ml-4 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-slate-500 transition hover:bg-gray-200 hover:text-slate-800"
                >

                    <i
                        data-lucide="x"
                        class="h-5 w-5"
                    ></i>

                </button>

            </div>


            {{-- INFOS CLIENT --}}
            <div class="grid shrink-0 grid-cols-1 gap-4 border-b border-gray-100 bg-gray-50 p-5 md:grid-cols-3">

                <div>

                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Client
                    </p>

                    <p
                        id="modalClient"
                        class="mt-1 text-sm font-bold text-slate-900"
                    >
                        -
                    </p>

                    <p
                        id="modalContact"
                        class="mt-1 text-xs text-slate-500"
                    >
                        -
                    </p>

                </div>

                <div>

                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Email
                    </p>

                    <p
                        id="modalEmail"
                        class="mt-1 break-all text-xs text-slate-700"
                    >
                        -
                    </p>

                </div>

                <div>

                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Téléphone
                    </p>

                    <p
                        id="modalTelephone"
                        class="mt-1 text-xs text-slate-700"
                    >
                        -
                    </p>

                </div>

            </div>


            {{-- CORPS --}}
            <div class="min-h-0 flex-1 overflow-y-auto">

                <div class="p-5 sm:p-6">

                    <div class="mb-4">

                        <h3 class="text-sm font-bold text-slate-900">
                            Articles commandés
                        </h3>

                        <p
                            id="modalNombreArticles"
                            class="mt-1 text-xs text-slate-400"
                        >
                            -
                        </p>

                    </div>


                    <div class="overflow-hidden rounded-2xl border border-gray-100">

                        <div class="overflow-x-auto">

                            <table class="w-full min-w-[650px]">

                                <thead>

                                    <tr class="border-b border-gray-100 bg-gray-50">

                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Désignation
                                        </th>

                                        <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Quantité
                                        </th>

                                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Prix unitaire
                                        </th>

                                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Total
                                        </th>

                                    </tr>

                                </thead>

                                <tbody
                                    id="modalArticles"
                                    class="divide-y divide-gray-100"
                                ></tbody>

                            </table>

                        </div>

                    </div>


                    {{-- TOTAL --}}
                    <div class="mt-5 flex justify-end">

                        <div class="w-full max-w-sm rounded-2xl bg-emerald-50 p-4">

                            <div class="flex items-center justify-between gap-4">

                                <span class="text-xs font-semibold text-emerald-800">
                                    Total commande
                                </span>

                                <span
                                    id="modalTotal"
                                    class="text-lg font-bold text-emerald-900"
                                >
                                    0 FCFA
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- FOOTER --}}
            <div class="relative z-20 shrink-0 border-t border-gray-200 bg-white shadow-[0_-8px_20px_rgba(15,23,42,0.05)]">

                {{-- DISPONIBILITÉ PARTIELLE --}}
                <div
                    id="zoneDisponibilitePartielle"
                    class="hidden max-h-[42vh] overflow-y-auto border-b border-amber-100 bg-amber-50"
                >

                    <div class="border-b border-amber-100 px-4 py-4">

                        <div class="flex items-start gap-3">

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">

                                <i
                                    data-lucide="package-search"
                                    class="h-5 w-5"
                                ></i>

                            </div>

                            <div class="min-w-0">

                                <p class="text-sm font-bold text-amber-900">
                                    Vérification de la disponibilité
                                </p>

                                <p class="mt-0.5 text-xs leading-relaxed text-amber-700">
                                    Tous les produits sont disponibles par défaut.
                                    Décochez uniquement les produits qui ne sont pas disponibles.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="px-4 py-4">

                        <div
                            id="listeDisponibiliteProduits"
                            class="space-y-2"
                        ></div>


                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-3">

                            <i
                                data-lucide="info"
                                class="mt-0.5 h-4 w-4 shrink-0 text-blue-600"
                            ></i>

                            <p class="text-[11px] leading-relaxed text-blue-700">
                                Le message envoyé au client sera généré automatiquement
                                à partir des produits disponibles et indisponibles.
                            </p>

                        </div>

                    </div>

                </div>


                {{-- ACTIONS --}}
                <div class="bg-white px-4 py-4 sm:px-6">

                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                        <div class="flex items-start gap-3">

                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">

                                <i
                                    data-lucide="clipboard-check"
                                    class="h-4 w-4"
                                ></i>

                            </div>

                            <div>

                                <p class="text-xs font-semibold text-slate-700">
                                    Vérification de la commande
                                </p>

                                <p class="mt-0.5 max-w-md text-[11px] leading-relaxed text-slate-400">
                                    Vérifiez la disponibilité des articles avant
                                    d'informer automatiquement le client par email.
                                </p>

                            </div>

                        </div>


                        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">

                            {{-- ANNULER --}}
                            <button
                                id="btnAnnulerDisponibilite"
                                type="button"
                                onclick="annulerDisponibilitePartielle()"
                                class="hidden inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50"
                            >

                                <i
                                    data-lucide="x"
                                    class="h-4 w-4"
                                ></i>

                                Annuler

                            </button>


                            {{-- INDISPONIBILITÉ --}}
                            <button
                                id="btnDisponibilitePartielle"
                                type="button"
                                onclick="afficherDisponibilitePartielle()"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-200 bg-white px-5 py-2.5 text-xs font-semibold text-amber-700 shadow-sm transition hover:bg-amber-50"
                            >

                                <i
                                    data-lucide="triangle-alert"
                                    class="h-4 w-4"
                                ></i>

                                Signaler une indisponibilité

                            </button>


                            {{-- DISPONIBILITÉ COMPLÈTE --}}
                            <button
                                id="btnConfirmerDisponibilite"
                                type="button"
                                onclick="confirmerDisponibilite()"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-6 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >

                                <i
                                    data-lucide="check-circle-2"
                                    class="h-4 w-4"
                                ></i>

                                Valider la disponibilité

                            </button>


                            {{-- ENVOYER PARTIEL --}}
                            <button
                                id="btnEnvoyerDisponibilite"
                                type="button"
                                onclick="envoyerDisponibilitePartielle()"
                                class="hidden inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-6 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >

                                <i
                                    data-lucide="send"
                                    class="h-4 w-4"
                                ></i>

                                Envoyer au client

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =============================================================
     DONNÉES
============================================================= --}}

<script>
    window.commandesData = @json($commandesData);
</script>


<script>

    let commandeActuelleId = null;

    const modal =
        document.getElementById('commandeModal');


    /*
    |--------------------------------------------------------------------------
    | RECHERCHE / FILTRES SERVEUR
    |--------------------------------------------------------------------------
    */

    const formulaireFiltres =
        document.getElementById('filtresCommandes');

    const inputRecherche =
        document.getElementById('rechercheCommandes');

    const inputStatut =
        document.getElementById('statutFiltre');

    const boutonsStatut =
        document.querySelectorAll('.filtre-statut');


    /*
    |--------------------------------------------------------------------------
    | FILTRE STATUT
    |--------------------------------------------------------------------------
    */

    boutonsStatut.forEach(function (bouton) {

        bouton.addEventListener('click', function () {

            const nouveauStatut =
                this.dataset.statut || 'toutes';

            inputStatut.value =
                nouveauStatut;

            formulaireFiltres.submit();

        });

    });


    /*
    |--------------------------------------------------------------------------
    | RECHERCHE
    |--------------------------------------------------------------------------
    */

    let timerRecherche = null;

    inputRecherche.addEventListener('input', function () {

        clearTimeout(timerRecherche);

        timerRecherche = setTimeout(function () {

            formulaireFiltres.submit();

        }, 500);

    });


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value)
    {
        const div =
            document.createElement('div');

        div.textContent =
            value ?? '';

        return div.innerHTML;
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT FCFA
    |--------------------------------------------------------------------------
    */

    function formatFCFA(nombre)
    {
        return new Intl.NumberFormat(
            'fr-FR',
            {
                maximumFractionDigits: 0
            }
        ).format(
            Number(nombre || 0)
        ) + ' FCFA';
    }


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    function obtenirTokenCSRF()
    {
        return document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
    }


    /*
    |--------------------------------------------------------------------------
    | RESET DISPONIBILITÉ
    |--------------------------------------------------------------------------
    */

    function reinitialiserDisponibilite()
    {
        const zone =
            document.getElementById(
                'zoneDisponibilitePartielle'
            );

        const liste =
            document.getElementById(
                'listeDisponibiliteProduits'
            );

        const boutonPartiel =
            document.getElementById(
                'btnDisponibilitePartielle'
            );

        const boutonComplet =
            document.getElementById(
                'btnConfirmerDisponibilite'
            );

        const boutonAnnuler =
            document.getElementById(
                'btnAnnulerDisponibilite'
            );

        const boutonEnvoyer =
            document.getElementById(
                'btnEnvoyerDisponibilite'
            );


        if (zone) {
            zone.classList.add('hidden');
        }

        if (liste) {
            liste.innerHTML = '';
        }

        if (boutonPartiel) {

            boutonPartiel.classList.remove(
                'hidden',
                'bg-amber-100',
                'border-amber-300'
            );
        }

        if (boutonComplet) {
            boutonComplet.classList.remove('hidden');
        }

        if (boutonAnnuler) {
            boutonAnnuler.classList.add('hidden');
        }

        if (boutonEnvoyer) {
            boutonEnvoyer.classList.add('hidden');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | OUVRIR COMMANDE
    |--------------------------------------------------------------------------
    */

    function ouvrirCommande(id)
    {
        const commande =
            window.commandesData[id];

        if (!commande) {

            console.error(
                'Commande introuvable :',
                id
            );

            return;
        }


        commandeActuelleId =
            commande.id;


        reinitialiserDisponibilite();


        const client =
            commande.type_client === 'entreprise'
            && commande.raison_sociale
                ? commande.raison_sociale
                : commande.nom;


        const contact =
            commande.type_client === 'entreprise'
            && commande.raison_sociale
                ? 'Contact : ' + commande.nom
                : 'Particulier';


        document.getElementById(
            'modalReference'
        ).textContent =
            commande.reference || '-';


        document.getElementById(
            'modalCommandeId'
        ).textContent =
            'Commande #' + commande.id;


        document.getElementById(
            'modalClient'
        ).textContent =
            client || '-';


        document.getElementById(
            'modalContact'
        ).textContent =
            contact;


        document.getElementById(
            'modalEmail'
        ).textContent =
            commande.email || '-';


        document.getElementById(
            'modalTelephone'
        ).textContent =
            commande.telephone || '-';


        const articles =
            Array.isArray(commande.items)
                ? commande.items
                : [];


        const totalUnites =
            articles.reduce(
                function (total, item) {

                    return total +
                        Number(item.quantite || 0);

                },
                0
            );


        document.getElementById(
            'modalNombreArticles'
        ).textContent =
            articles.length +
            ' article(s) • ' +
            totalUnites +
            ' unité(s)';


        const articlesContainer =
            document.getElementById(
                'modalArticles'
            );


        if (articles.length === 0) {

            articlesContainer.innerHTML = `
                <tr>
                    <td
                        colspan="4"
                        class="px-4 py-10 text-center text-xs text-slate-400"
                    >
                        Aucun article enregistré pour cette commande.
                    </td>
                </tr>
            `;

        } else {

            articlesContainer.innerHTML =
                articles.map(function (item) {

                    let statutDisponibilite = '';

                    if (item.disponible === true) {

                        statutDisponibilite = `
                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-semibold text-emerald-700">
                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                Disponible
                            </span>
                        `;

                    } else if (item.disponible === false) {

                        statutDisponibilite = `
                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[9px] font-semibold text-red-700">
                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                Indisponible
                            </span>
                        `;

                    } else {

                        statutDisponibilite = `
                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[9px] font-semibold text-gray-500">
                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                Non vérifié
                            </span>
                        `;
                    }


                    return `
                        <tr class="transition hover:bg-gray-50">

                            <td class="px-4 py-4">

                                <p class="text-xs font-semibold text-slate-800">
                                    ${escapeHtml(item.designation)}
                                </p>

                                ${statutDisponibilite}

                            </td>

                            <td class="px-4 py-4 text-center">

                                <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-gray-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                    ${escapeHtml(item.quantite)}
                                </span>

                            </td>

                            <td class="px-4 py-4 text-right">

                                <span class="text-xs font-medium text-slate-600">
                                    ${formatFCFA(item.prix_unitaire)}
                                </span>

                            </td>

                            <td class="px-4 py-4 text-right">

                                <span class="text-xs font-bold text-slate-900">
                                    ${formatFCFA(item.total_ligne)}
                                </span>

                            </td>

                        </tr>
                    `;

                }).join('');
        }


        document.getElementById(
            'modalTotal'
        ).textContent =
            formatFCFA(commande.total);


        modal.classList.remove('hidden');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.classList.add(
            'overflow-hidden'
        );


        if (window.lucide) {
            lucide.createIcons();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FERMER COMMANDE
    |--------------------------------------------------------------------------
    */

    function fermerCommande()
    {
        modal.classList.add('hidden');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.classList.remove(
            'overflow-hidden'
        );

        commandeActuelleId = null;

        reinitialiserDisponibilite();
    }


    /*
    |--------------------------------------------------------------------------
    | AFFICHER DISPONIBILITÉ PARTIELLE
    |--------------------------------------------------------------------------
    */

    function afficherDisponibilitePartielle()
    {
        if (!commandeActuelleId) {
            return;
        }


        const commande =
            window.commandesData[
                commandeActuelleId
            ];


        if (!commande) {
            return;
        }


        const zone =
            document.getElementById(
                'zoneDisponibilitePartielle'
            );

        const liste =
            document.getElementById(
                'listeDisponibiliteProduits'
            );

        const boutonPartiel =
            document.getElementById(
                'btnDisponibilitePartielle'
            );

        const boutonComplet =
            document.getElementById(
                'btnConfirmerDisponibilite'
            );

        const boutonAnnuler =
            document.getElementById(
                'btnAnnulerDisponibilite'
            );

        const boutonEnvoyer =
            document.getElementById(
                'btnEnvoyerDisponibilite'
            );


        if (!zone || !liste) {
            return;
        }


        const articles =
            Array.isArray(commande.items)
                ? commande.items
                : [];


        if (articles.length === 0) {

            liste.innerHTML = `
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                    <p class="text-xs text-slate-500">
                        Aucun produit dans cette commande.
                    </p>
                </div>
            `;

        } else {

            liste.innerHTML =
                articles.map(function (item) {

                    return `
                        <label
                            class="produit-disponibilite flex cursor-pointer items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 transition hover:border-emerald-300"
                        >

                            <input
                                type="checkbox"
                                class="checkbox-disponibilite h-4 w-4 shrink-0 cursor-pointer rounded border-gray-300 accent-emerald-600"
                                data-item-id="${Number(item.id)}"
                                value="${Number(item.id)}"
                                checked
                            >


                            <div class="min-w-0 flex-1">

                                <p class="truncate text-xs font-semibold text-slate-700">
                                    ${escapeHtml(item.designation)}
                                </p>

                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    Quantité : ${escapeHtml(item.quantite)}
                                </p>

                            </div>


                            <span
                                class="statut-produit-disponibilite shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold text-emerald-700"
                            >
                                Disponible
                            </span>

                        </label>
                    `;

                }).join('');


            liste
                .querySelectorAll('.checkbox-disponibilite')
                .forEach(function (checkbox) {

                    checkbox.addEventListener(
                        'change',
                        function () {

                            const ligne =
                                this.closest(
                                    '.produit-disponibilite'
                                );


                            const badge =
                                ligne.querySelector(
                                    '.statut-produit-disponibilite'
                                );


                            if (this.checked) {

                                ligne.classList.remove(
                                    'border-red-200',
                                    'bg-red-50'
                                );

                                ligne.classList.add(
                                    'border-emerald-200',
                                    'bg-emerald-50'
                                );

                                badge.textContent =
                                    'Disponible';

                                badge.className =
                                    'statut-produit-disponibilite shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold text-emerald-700';

                            } else {

                                ligne.classList.remove(
                                    'border-emerald-200',
                                    'bg-emerald-50'
                                );

                                ligne.classList.add(
                                    'border-red-200',
                                    'bg-red-50'
                                );

                                badge.textContent =
                                    'Indisponible';

                                badge.className =
                                    'statut-produit-disponibilite shrink-0 rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-semibold text-red-700';

                            }

                        }
                    );

                });
        }


        zone.classList.remove('hidden');


        if (boutonPartiel) {
            boutonPartiel.classList.add('hidden');
        }

        if (boutonComplet) {
            boutonComplet.classList.add('hidden');
        }

        if (boutonAnnuler) {
            boutonAnnuler.classList.remove('hidden');
        }

        if (boutonEnvoyer) {
            boutonEnvoyer.classList.remove('hidden');
        }


        if (window.lucide) {
            lucide.createIcons();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ANNULER
    |--------------------------------------------------------------------------
    */

    function annulerDisponibilitePartielle()
    {
        reinitialiserDisponibilite();
    }


    /*
    |--------------------------------------------------------------------------
    | DISPONIBILITÉ COMPLÈTE
    |--------------------------------------------------------------------------
    */

    async function confirmerDisponibilite()
    {
        if (!commandeActuelleId) {
            return;
        }


        const commande =
            window.commandesData[
                commandeActuelleId
            ];


        if (!commande) {
            return;
        }


        const bouton =
            document.getElementById(
                'btnConfirmerDisponibilite'
            );


        if (!bouton) {
            return;
        }


        const texteOriginal =
            bouton.innerHTML;


        bouton.disabled = true;

        bouton.innerHTML = `
            <i
                data-lucide="loader-circle"
                class="h-4 w-4 animate-spin"
            ></i>
            Vérification...
        `;


        if (window.lucide) {
            lucide.createIcons();
        }


        try {

            const items =
                (commande.items || []).map(
                    function (item) {

                        return {
                            id: Number(item.id),
                            disponible: true
                        };

                    }
                );


            const response =
                await fetch(
                    `/commande/${commandeActuelleId}/disponibilite`,
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': obtenirTokenCSRF(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },

                        body: JSON.stringify({
                            items: items
                        })
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    'Impossible de confirmer la disponibilité.'
                );
            }


            fermerCommande();


            afficherNotification(
                data.message ||
                'Tous les produits sont disponibles. Le client a été informé par email.',
                'success'
            );


            setTimeout(
                function () {
                    window.location.reload();
                },
                1200
            );


        } catch (error) {

            console.error(
                'Erreur disponibilité complète :',
                error
            );


            afficherNotification(
                error.message ||
                'Impossible de confirmer la disponibilité.',
                'error'
            );


            bouton.disabled = false;

            bouton.innerHTML =
                texteOriginal;


            if (window.lucide) {
                lucide.createIcons();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DISPONIBILITÉ PARTIELLE
    |--------------------------------------------------------------------------
    */

    async function envoyerDisponibilitePartielle()
    {
        if (!commandeActuelleId) {
            return;
        }


        const checkboxes =
            document.querySelectorAll(
                '.checkbox-disponibilite'
            );


        if (!checkboxes.length) {

            afficherNotification(
                'Aucun produit à vérifier.',
                'error'
            );

            return;
        }


        const items =
            Array.from(
                checkboxes
            ).map(
                function (checkbox) {

                    return {
                        id: Number(
                            checkbox.dataset.itemId
                        ),

                        disponible:
                            checkbox.checked
                    };

                }
            );


        const auMoinsUnIndisponible =
            items.some(
                function (item) {
                    return item.disponible === false;
                }
            );


        if (!auMoinsUnIndisponible) {

            afficherNotification(
                'Décochez au moins un produit pour signaler une indisponibilité.',
                'error'
            );

            return;
        }


        const bouton =
            document.getElementById(
                'btnEnvoyerDisponibilite'
            );


        if (!bouton) {
            return;
        }


        const texteOriginal =
            bouton.innerHTML;


        bouton.disabled = true;


        bouton.innerHTML = `
            <i
                data-lucide="loader-circle"
                class="h-4 w-4 animate-spin"
            ></i>
            Envoi...
        `;


        if (window.lucide) {
            lucide.createIcons();
        }


        try {

            const response =
                await fetch(
                    `/commande/${commandeActuelleId}/indisponibilite`,
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': obtenirTokenCSRF(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },

                        body: JSON.stringify({
                            items: items
                        })
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    'Impossible d’enregistrer les disponibilités.'
                );
            }


            fermerCommande();


            afficherNotification(
                data.message ||
                'Les disponibilités ont été enregistrées et le client a été informé par email.',
                'success'
            );


            setTimeout(
                function () {
                    window.location.reload();
                },
                1200
            );


        } catch (error) {

            console.error(
                'Erreur disponibilité partielle :',
                error
            );


            afficherNotification(
                error.message ||
                'Impossible d’envoyer les informations au client.',
                'error'
            );


            bouton.disabled = false;

            bouton.innerHTML =
                texteOriginal;


            if (window.lucide) {
                lucide.createIcons();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION
    |--------------------------------------------------------------------------
    */

    function afficherNotification(
        message,
        type = 'success'
    ) {

        const ancienne =
            document.getElementById(
                'notificationCommande'
            );


        if (ancienne) {
            ancienne.remove();
        }


        const notification =
            document.createElement('div');


        notification.id =
            'notificationCommande';


        const estSucces =
            type === 'success';


        notification.className =
            `
            fixed right-5 top-5 z-[100]
            flex max-w-md items-start gap-3
            rounded-2xl border
            px-5 py-4 shadow-2xl
            ${
                estSucces
                    ? 'border-emerald-200 bg-white'
                    : 'border-red-200 bg-white'
            }
            `;


        notification.innerHTML = `

            <div
                class="
                    flex h-9 w-9 shrink-0 items-center justify-center
                    rounded-xl
                    ${
                        estSucces
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-red-100 text-red-700'
                    }
                "
            >

                <i
                    data-lucide="${
                        estSucces
                            ? 'check-circle-2'
                            : 'circle-alert'
                    }"
                    class="h-5 w-5"
                ></i>

            </div>


            <div class="min-w-0 flex-1">

                <p class="text-xs font-bold ${
                    estSucces
                        ? 'text-emerald-800'
                        : 'text-red-800'
                }">

                    ${
                        estSucces
                            ? 'Opération réussie'
                            : 'Erreur'
                    }

                </p>


                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    ${escapeHtml(message)}
                </p>

            </div>


            <button
                type="button"
                onclick="this.parentElement.remove()"
                class="text-slate-400 transition hover:text-slate-700"
            >

                <i
                    data-lucide="x"
                    class="h-4 w-4"
                ></i>

            </button>

        `;


        document.body.appendChild(
            notification
        );


        if (window.lucide) {
            lucide.createIcons();
        }


        setTimeout(
            function () {

                if (notification) {
                    notification.remove();
                }

            },
            5000
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ESC
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                !modal.classList.contains('hidden')
            ) {

                fermerCommande();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | INITIALISATION
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            if (window.lucide) {
                lucide.createIcons();
            }

        }
    );

</script>


</body>

</html>
