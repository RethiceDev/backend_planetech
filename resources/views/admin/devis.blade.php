<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Planetech Administration - Demandes de devis</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/lucide@latest"></script>

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
        }
    </script>

</head>


<body class="bg-gray-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">

    @include('admin.aside')


    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        <main class="p-8 space-y-6">


            {{-- =====================================================
                 MESSAGES
            ====================================================== --}}

            @if(session('success'))

                <div
                    id="successAlert"
                    class="relative flex items-start gap-4 p-4 rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-800 shadow-sm"
                >

                    <div class="h-10 w-10 shrink-0 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">

                        <i
                            data-lucide="circle-check"
                            class="w-5 h-5"
                        ></i>

                    </div>

                    <div class="flex-1 min-w-0">

                        <p class="text-sm font-bold">
                            Opération réussie
                        </p>

                        <p class="text-xs mt-1 leading-relaxed">
                            {{ session('success') }}
                        </p>

                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('successAlert').remove()"
                        class="shrink-0 h-8 w-8 rounded-lg hover:bg-emerald-100 flex items-center justify-center transition"
                    >

                        <i
                            data-lucide="x"
                            class="w-4 h-4"
                        ></i>

                    </button>

                </div>

            @endif


            @if(session('error'))

                <div
                    id="errorAlert"
                    class="relative flex items-start gap-4 p-4 rounded-2xl border border-red-200 bg-red-50 text-red-800 shadow-sm"
                >

                    <div class="h-10 w-10 shrink-0 rounded-xl bg-red-100 text-red-700 flex items-center justify-center">

                        <i
                            data-lucide="circle-alert"
                            class="w-5 h-5"
                        ></i>

                    </div>

                    <div class="flex-1 min-w-0">

                        <p class="text-sm font-bold">
                            Attention
                        </p>

                        <p class="text-xs mt-1 leading-relaxed">
                            {{ session('error') }}
                        </p>

                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('errorAlert').remove()"
                        class="shrink-0 h-8 w-8 rounded-lg hover:bg-red-100 flex items-center justify-center transition"
                    >

                        <i
                            data-lucide="x"
                            class="w-4 h-4"
                        ></i>

                    </button>

                </div>

            @endif


            @if($errors->any())

                <div
                    id="validationAlert"
                    class="relative flex items-start gap-4 p-4 rounded-2xl border border-amber-200 bg-amber-50 text-amber-800 shadow-sm"
                >

                    <div class="h-10 w-10 shrink-0 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">

                        <i
                            data-lucide="triangle-alert"
                            class="w-5 h-5"
                        ></i>

                    </div>

                    <div class="flex-1 min-w-0">

                        <p class="text-sm font-bold">
                            Vérifiez les informations
                        </p>

                        <ul class="text-xs mt-1 leading-relaxed list-disc list-inside">

                            @foreach($errors->all() as $error)

                                <li>
                                    {{ $error }}
                                </li>

                            @endforeach

                        </ul>

                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('validationAlert').remove()"
                        class="shrink-0 h-8 w-8 rounded-lg hover:bg-amber-100 flex items-center justify-center transition"
                    >

                        <i
                            data-lucide="x"
                            class="w-4 h-4"
                        ></i>

                    </button>

                </div>

            @endif


            {{-- =====================================================
                 ENTÊTE
            ====================================================== --}}

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">



                <div class="flex items-center gap-3">

                        <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="file-text" class="w-5 h-5"></i>
                        </div>

                       <div>

                            <h1 class="text-2xl font-bold text-slate-900">
                                Toutes les demandes
                            </h1>

                            <p class="text-sm text-slate-muted mt-1">
                                Consultez les demandes de devis envoyées par vos clients.
                            </p>

                        </div>
                </div>


                <div class="bg-white border border-gray-100 rounded-xl px-4 py-3 shadow-sm flex items-center gap-3">

                    <div class="h-9 w-9 rounded-lg bg-forest-50 text-forest-700 flex items-center justify-center">

                        <i
                            data-lucide="file-text"
                            class="w-5 h-5"
                        ></i>

                    </div>

                    <div>

                        <p class="text-[11px] text-slate-muted">
                            Total des demandes
                        </p>

                        <p class="text-lg font-bold text-slate-900">
                            {{ $totalDemandes }}
                        </p>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 FILTRES
            ====================================================== --}}

            <form
                id="devisFilters"
                method="GET"
                action="{{ url()->current() }}"
                class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4"
            >

                {{-- STATUT CONSERVÉ POUR LES BOUTONS --}}
                <input
                    type="hidden"
                    name="statut"
                    id="statutInput"
                    value="{{ $statut }}"
                >


                <div class="flex flex-col xl:flex-row gap-4 xl:items-center">


                    {{-- FILTRES STATUT --}}
                    <div class="flex items-center gap-2 flex-wrap flex-1">


                        {{-- TOUTES --}}
                        <button
                            type="button"
                            data-status="toutes"
                            class="status-filter px-4 py-2 rounded-xl font-medium text-xs flex items-center gap-2 shadow-sm transition
                            {{ $statut === 'toutes'
                                ? 'bg-emerald-600 text-white'
                                : 'bg-white border border-gray-200 text-slate-600 hover:bg-gray-50'
                            }}"
                        >

                            Toutes

                            <span
                                class="{{ $statut === 'toutes'
                                    ? 'bg-emerald-700/60'
                                    : 'bg-gray-100 text-slate-600'
                                }} px-1.5 py-0.5 rounded-md text-[10px]"
                            >
                                {{ $totalDemandes }}
                            </span>

                        </button>


                        {{-- NON TRAITÉ --}}
                        <button
                            type="button"
                            data-status="en_attente"
                            class="status-filter px-4 py-2 rounded-xl font-medium text-xs flex items-center gap-2 transition
                            {{ $statut === 'en_attente'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white border border-gray-200 text-slate-600 hover:bg-gray-50'
                            }}"
                        >

                            Non traité(s)

                            <span class="bg-gray-100 text-slate-600 px-1.5 py-0.5 rounded-md text-[10px]">
                                {{ $demandesNonTraitees }}
                            </span>

                        </button>


                        {{-- EN COURS --}}
                        <button
                            type="button"
                            data-status="en_cours"
                            class="status-filter px-4 py-2 rounded-xl font-medium text-xs flex items-center gap-2 transition
                            {{ $statut === 'en_cours'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white border border-gray-200 text-slate-600 hover:bg-gray-50'
                            }}"
                        >

                            En cours

                            <span class="bg-gray-100 text-slate-600 px-1.5 py-0.5 rounded-md text-[10px]">
                                {{ $demandesEnCours }}
                            </span>

                        </button>


                        {{-- TRAITÉ --}}
                        <button
                            type="button"
                            data-status="traite"
                            class="status-filter px-4 py-2 rounded-xl font-medium text-xs flex items-center gap-2 transition
                            {{ $statut === 'traite'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white border border-gray-200 text-slate-600 hover:bg-gray-50'
                            }}"
                        >

                            Traité

                            <span class="bg-gray-100 text-slate-600 px-1.5 py-0.5 rounded-md text-[10px]">
                                {{ $demandesTraitees }}
                            </span>

                        </button>


                        {{-- AUJOURD'HUI --}}
                        <button
                            type="button"
                            data-status="aujourd_hui"
                            class="status-filter px-4 py-2 rounded-xl font-medium text-xs flex items-center gap-2 transition
                            {{ $statut === 'aujourd_hui'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white border border-gray-200 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700'
                            }}"
                        >

                            <i
                                data-lucide="calendar-days"
                                class="w-3.5 h-3.5"
                            ></i>

                            Nouveau

                            <span
                                class="{{ $statut === 'aujourd_hui'
                                    ? 'bg-emerald-700/60 text-white'
                                    : 'bg-emerald-50 text-emerald-700'
                                }} px-1.5 py-0.5 rounded-md text-[10px]"
                            >
                                {{ $demandesAujourdHui }}
                            </span>

                        </button>


                        {{-- CETTE SEMAINE --}}
                        <button
                            type="button"
                            data-status="cette_semaine"
                            class="status-filter px-4 py-2 rounded-xl font-medium text-xs flex items-center gap-2 transition
                            {{ $statut === 'cette_semaine'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white border border-gray-200 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700'
                            }}"
                        >

                            <i
                                data-lucide="calendar-range"
                                class="w-3.5 h-3.5"
                            ></i>

                            Cette semaine

                            <span
                                class="{{ $statut === 'cette_semaine'
                                    ? 'bg-emerald-700/60 text-white'
                                    : 'bg-emerald-50 text-emerald-700'
                                }} px-1.5 py-0.5 rounded-md text-[10px]"
                            >
                                {{ $demandesCetteSemaine }}
                            </span>

                        </button>


                        {{-- ANNULÉ --}}
                        <button
                            type="button"
                            data-status="annule"
                            class="status-filter px-4 py-2 rounded-xl font-medium text-xs flex items-center gap-2 transition
                            {{ $statut === 'annule'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white border border-gray-200 text-slate-600 hover:bg-gray-50'
                            }}"
                        >

                            Annulé

                            <span class="bg-gray-100 text-slate-600 px-1.5 py-0.5 rounded-md text-[10px]">
                                {{ $demandesAnnulees }}
                            </span>

                        </button>

                    </div>


                    {{-- RECHERCHE --}}
                    <div class="flex items-center gap-2 w-full xl:w-auto">

                        <div class="relative w-full sm:w-72">

                            <i
                                data-lucide="search"
                                class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"
                            ></i>

                            <input
                                type="text"
                                id="searchInput"
                                name="recherche"
                                value="{{ $recherche }}"
                                placeholder="Nom, société, email, référence..."
                                autocomplete="off"
                                class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-gray-200 text-xs text-slate-700 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100 bg-white"
                            >

                        </div>


                        <button
                            type="button"
                            id="filterButton"
                            class="shrink-0 h-10 w-10 rounded-xl border border-gray-200 bg-white text-slate-600 hover:bg-gray-50 hover:text-emerald-700 transition flex items-center justify-center"
                            title="Filtres avancés"
                        >

                            <i
                                data-lucide="sliders-horizontal"
                                class="w-4 h-4"
                            ></i>

                        </button>

                    </div>

                </div>


                {{-- FILTRES AVANCÉS --}}
                <div
                    id="advancedFilters"
                    class="{{ ($typeClient !== 'all' || $domaine !== '') ? '' : 'hidden' }} mt-4 pt-4 border-t border-gray-100"
                >

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


                        {{-- TYPE CLIENT --}}
                        <div>

                            <label
                                for="clientTypeFilter"
                                class="block text-xs font-semibold text-slate-700 mb-1.5"
                            >
                                Type de client
                            </label>

                            <select
                                id="clientTypeFilter"
                                name="type_client"
                                class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:border-emerald-600"
                            >

                                <option
                                    value="all"
                                    {{ $typeClient === 'all' ? 'selected' : '' }}
                                >
                                    Tous les clients
                                </option>

                                <option
                                    value="societe"
                                    {{ $typeClient === 'societe' ? 'selected' : '' }}
                                >
                                    Société
                                </option>

                                <option
                                    value="particulier"
                                    {{ $typeClient === 'particulier' ? 'selected' : '' }}
                                >
                                    Personne physique
                                </option>

                            </select>

                        </div>


                        {{-- DOMAINE --}}
                        <div>

                            <label
                                for="domainFilter"
                                class="block text-xs font-semibold text-slate-700 mb-1.5"
                            >
                                Domaine
                            </label>

                            <input
                                type="text"
                                id="domainFilter"
                                name="domaine"
                                value="{{ $domaine }}"
                                placeholder="Ex. informatique"
                                class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:border-emerald-600"
                            >

                        </div>


                        {{-- RESET --}}
                        <div class="flex items-end">

                            <a
                                href="{{ url()->current() }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-medium text-slate-600 hover:bg-gray-50 flex items-center justify-center gap-2"
                            >

                                <i
                                    data-lucide="rotate-ccw"
                                    class="w-3.5 h-3.5"
                                ></i>

                                Réinitialiser les filtres

                            </a>

                        </div>

                    </div>

                </div>

            </form>


            {{-- =====================================================
                 LISTE
            ====================================================== --}}

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">


                {{-- HEADER TABLEAU --}}
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                    <div>

                        <h3 class="font-bold text-slate-900 text-sm">
                            Liste des demandes
                        </h3>

                        <p class="text-xs text-slate-muted mt-0.5">

                            @if($listes->total() > 0)

                                Affichage de

                                <span class="font-semibold text-slate-700">
                                    {{ $listes->firstItem() }}
                                </span>

                                à

                                <span class="font-semibold text-slate-700">
                                    {{ $listes->lastItem() }}
                                </span>

                                sur

                                <span class="font-semibold text-slate-700">
                                    {{ $listes->total() }}
                                </span>

                                demande{{ $listes->total() > 1 ? 's' : '' }}

                            @else

                                Aucune demande trouvée

                            @endif

                        </p>

                    </div>


                    <div class="inline-flex w-fit items-center gap-2 rounded-full bg-gray-50 px-3 py-1.5 text-[10px] font-semibold text-slate-500">

                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                        @switch($statut)

                            @case('en_attente')
                                Demandes non traitées
                                @break

                            @case('en_cours')
                                Demandes en cours
                                @break

                            @case('traite')
                                Demandes traitées
                                @break

                            @case('aujourd_hui')
                                Nouvelles aujourd'hui
                                @break

                            @case('cette_semaine')
                                Nouvelles cette semaine
                                @break

                            @case('annule')
                                Demandes annulées
                                @break

                            @default
                                Toutes les demandes

                        @endswitch

                    </div>

                </div>


                {{-- TABLE --}}
                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1100px]">

                        <thead>

                        <tr class="bg-gray-50/80 border-b border-gray-100">

                            <th class="text-left px-6 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-500">
                                Référence
                            </th>

                            <th class="text-left px-4 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-500">
                                Client
                            </th>

                            <th class="text-left px-4 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-500">
                                Contact
                            </th>

                            <th class="text-left px-4 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-500">
                                Domaine
                            </th>

                            <th class="text-left px-4 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-500">
                                Besoins
                            </th>

                            <th class="text-left px-4 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-500">
                                Statut
                            </th>

                            <th class="text-left px-4 py-3 text-[10px] uppercase tracking-wider font-bold text-slate-500">
                                Date
                            </th>

                            <th class="px-4 py-3"></th>

                        </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">


                        @forelse($listes as $demande)

                            @php

                                $isSociete =
                                    $demande->type_client === 'societe';

                                $rawStatus =
                                    $demande->statut ?? 'en_attente';

                                $status =
                                    in_array(
                                        $rawStatus,
                                        [
                                            'chiffree',
                                            'envoye',
                                            'traite',
                                        ],
                                        true
                                    )
                                        ? 'traite'
                                        : $rawStatus;


                                $statusLabels = [

                                    'en_attente' =>
                                        'Non traité',

                                    'en_cours' =>
                                        'En cours',

                                    'traite' =>
                                        'Traité',

                                    'annule' =>
                                        'Annulé',
                                ];


                                $statusClasses = [

                                    'en_attente' =>
                                        'bg-blue-50 text-blue-700 border-blue-100',

                                    'en_cours' =>
                                        'bg-amber-50 text-amber-700 border-amber-100',

                                    'traite' =>
                                        'bg-green-50 text-green-700 border-green-100',

                                    'annule' =>
                                        'bg-red-50 text-red-700 border-red-100',
                                ];


                                $statusIcons = [

                                    'en_attente' =>
                                        'clock-3',

                                    'en_cours' =>
                                        'loader-circle',

                                    'traite' =>
                                        'check-circle-2',

                                    'annule' =>
                                        'x-circle',
                                ];

                            @endphp


                            <tr class="hover:bg-gray-50/70 transition-colors">


                                {{-- RÉFÉRENCE --}}
                                <td class="px-6 py-4">

                                    <p class="text-xs font-bold text-slate-900">
                                        {{ $demande->reference }}
                                    </p>

                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        #{{ $demande->id }}
                                    </p>

                                </td>


                                {{-- CLIENT --}}
                                <td class="px-4 py-4">

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0
                                            {{ $isSociete
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-blue-50 text-blue-700'
                                            }}"
                                        >

                                            <i
                                                data-lucide="{{ $isSociete ? 'building-2' : 'user-round' }}"
                                                class="w-4 h-4"
                                            ></i>

                                        </div>


                                        <div class="min-w-0">

                                            @if($isSociete)

                                                <p class="text-xs font-bold text-slate-900 truncate max-w-[180px]">
                                                    {{ $demande->raison_sociale }}
                                                </p>

                                                <p class="text-[10px] text-slate-500 mt-0.5">
                                                    {{ $demande->nom }}
                                                </p>

                                                <span class="inline-flex mt-1 px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[9px] font-medium">
                                                    Société
                                                </span>

                                            @else

                                                <p class="text-xs font-bold text-slate-900 truncate max-w-[180px]">
                                                    {{ $demande->nom }}
                                                </p>

                                                <span class="inline-flex mt-1 px-1.5 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[9px] font-medium">
                                                    Personne physique
                                                </span>

                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- CONTACT --}}
                                <td class="px-4 py-4">

                                    <div class="space-y-1">

                                        <p class="text-xs text-slate-700 flex items-center gap-1.5">

                                            <i
                                                data-lucide="mail"
                                                class="w-3 h-3 text-slate-400"
                                            ></i>

                                            {{ $demande->email }}

                                        </p>

                                        <p class="text-[11px] text-slate-500 flex items-center gap-1.5">

                                            <i
                                                data-lucide="phone"
                                                class="w-3 h-3 text-slate-400"
                                            ></i>

                                            {{ $demande->indicatif }}
                                            {{ $demande->telephone }}

                                        </p>

                                    </div>

                                </td>


                                {{-- DOMAINE --}}
                                <td class="px-4 py-4">

                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-slate-600 text-[10px] font-medium">

                                        {{ $demande->domaine }}

                                    </span>

                                </td>


                                {{-- BESOINS --}}
                                <td class="px-4 py-4 max-w-[230px]">

                                    <p
                                        class="text-xs text-slate-600 line-clamp-2 leading-relaxed"
                                        title="{{ $demande->besoins }}"
                                    >
                                        {{ $demande->besoins }}
                                    </p>

                                </td>


                                {{-- STATUT --}}
                                <td class="px-4 py-4">

                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-[10px] font-semibold {{ $statusClasses[$status] ?? 'bg-gray-50 text-gray-600 border-gray-100' }}"
                                    >

                                        <i
                                            data-lucide="{{ $statusIcons[$status] ?? 'circle' }}"
                                            class="w-3 h-3"
                                        ></i>

                                        {{ $statusLabels[$status] ?? ucfirst($status) }}

                                    </span>

                                </td>


                                {{-- DATE --}}
                                <td class="px-4 py-4">

                                    <p class="text-xs font-medium text-slate-700">

                                        {{
                                            $demande->created_at
                                                ? $demande->created_at->format('d/m/Y')
                                                : '-'
                                        }}

                                    </p>

                                    <p class="text-[10px] text-slate-400 mt-0.5">

                                        {{
                                            $demande->created_at
                                                ? $demande->created_at->format('H:i')
                                                : '-'
                                        }}

                                    </p>

                                </td>


                                {{-- ACTION --}}
                                <td class="px-4 py-4">

                                    <button
                                        type="button"
                                        class="view-devis h-9 w-9 rounded-xl border border-gray-200 text-slate-500 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-100 flex items-center justify-center transition"
                                        title="Voir la demande"

                                        data-id="{{ $demande->id }}"

                                        data-reference="{{ $demande->reference }}"

                                        data-type="{{ $demande->type_client }}"

                                        data-raison-sociale="{{ $demande->raison_sociale ?? '' }}"

                                        data-nom="{{ $demande->nom }}"

                                        data-email="{{ $demande->email }}"

                                        data-indicatif="{{ $demande->indicatif }}"

                                        data-telephone="{{ $demande->telephone }}"

                                        data-domaine="{{ $demande->domaine }}"

                                        data-besoins="{{ $demande->besoins }}"

                                        data-statut="{{ $status }}"

                                        data-date="{{ $demande->created_at?->format('d/m/Y à H:i') }}"

                                        data-chiffrer-url="{{ url('/demandes/' . $demande->id . '/creer-devis') }}"

                                        data-devis-url="{{ $demande->devis
                                            ? route('admin.devis.pdf', $demande->id)
                                            : ''
                                        }}"
                                    >

                                        <i
                                            data-lucide="eye"
                                            class="w-4 h-4"
                                        ></i>

                                    </button>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="px-6 py-20 text-center"
                                >

                                    <div class="mx-auto h-14 w-14 rounded-2xl bg-forest-50 text-forest-700 flex items-center justify-center mb-4">

                                        <i
                                            data-lucide="inbox"
                                            class="w-7 h-7"
                                        ></i>

                                    </div>

                                    <h3 class="font-bold text-slate-900 text-sm">
                                        Aucune demande de devis
                                    </h3>

                                    <p class="text-xs text-slate-muted mt-1 max-w-sm mx-auto">
                                        Aucune demande ne correspond aux filtres actuels.
                                    </p>


                                    @if(
                                        $recherche ||
                                        $domaine ||
                                        $typeClient !== 'all' ||
                                        $statut !== 'toutes'
                                    )

                                        <a
                                            href="{{ url()->current() }}"
                                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition"
                                        >

                                            <i
                                                data-lucide="rotate-ccw"
                                                class="w-4 h-4"
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

                @if($listes->hasPages())

                    <div class="border-t border-gray-100 bg-white px-6 py-4">

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">


                            {{-- INFORMATIONS --}}
                            <div class="text-xs text-slate-500">

                                Affichage de

                                <span class="font-semibold text-slate-700">
                                    {{ $listes->firstItem() }}
                                </span>

                                à

                                <span class="font-semibold text-slate-700">
                                    {{ $listes->lastItem() }}
                                </span>

                                sur

                                <span class="font-semibold text-slate-700">
                                    {{ $listes->total() }}
                                </span>

                                demande{{ $listes->total() > 1 ? 's' : '' }}

                            </div>


                            {{-- NAVIGATION --}}
                            <div class="flex items-center gap-2">


                                {{-- PRÉCÉDENT --}}
                                @if($listes->onFirstPage())

                                    <span
                                        class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400"
                                    >

                                        <i
                                            data-lucide="chevron-left"
                                            class="w-4 h-4"
                                        ></i>

                                        Précédent

                                    </span>

                                @else

                                    <a
                                        href="{{ $listes->previousPageUrl() }}"
                                        class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                                    >

                                        <i
                                            data-lucide="chevron-left"
                                            class="w-4 h-4"
                                        ></i>

                                        Précédent

                                    </a>

                                @endif


                                {{-- NUMÉROS --}}
                                <div class="hidden sm:flex items-center gap-1">

                                    @foreach(
                                        $listes->getUrlRange(
                                            max(
                                                1,
                                                $listes->currentPage() - 2
                                            ),
                                            min(
                                                $listes->lastPage(),
                                                $listes->currentPage() + 2
                                            )
                                        )
                                        as $page => $url
                                    )

                                        @if(
                                            $page ===
                                            $listes->currentPage()
                                        )

                                            <span
                                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-emerald-600 px-3 text-xs font-bold text-white shadow-sm"
                                            >
                                                {{ $page }}
                                            </span>

                                        @else

                                            <a
                                                href="{{ $url }}"
                                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                                            >
                                                {{ $page }}
                                            </a>

                                        @endif

                                    @endforeach

                                </div>


                                {{-- MOBILE --}}
                                <span
                                    class="inline-flex sm:hidden h-9 min-w-9 items-center justify-center rounded-xl bg-slate-100 px-3 text-xs font-bold text-slate-700"
                                >
                                    {{ $listes->currentPage() }}
                                </span>


                                {{-- SUIVANT --}}
                                @if($listes->hasMorePages())

                                    <a
                                        href="{{ $listes->nextPageUrl() }}"
                                        class="inline-flex items-center justify-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition"
                                    >

                                        Suivant

                                        <i
                                            data-lucide="chevron-right"
                                            class="w-4 h-4"
                                        ></i>

                                    </a>

                                @else

                                    <span
                                        class="inline-flex items-center justify-center gap-1 rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-400"
                                    >

                                        Suivant

                                        <i
                                            data-lucide="chevron-right"
                                            class="w-4 h-4"
                                        ></i>

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


{{-- =====================================================
     MODAL DETAIL
===================================================== --}}

<div
    id="devisModal"
    class="fixed inset-0 z-[9999] hidden"
    aria-hidden="true"
>

    <div
        id="modalOverlay"
        class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"
    ></div>


    <div class="relative z-10 min-h-screen flex items-center justify-center p-4 sm:p-6">

        <div
            id="modalContent"
            class="w-full max-w-4xl max-h-[92vh] overflow-hidden bg-white rounded-3xl shadow-2xl flex flex-col"
        >


            {{-- HEADER --}}
            <div class="relative bg-gradient-to-br from-emerald-950 via-emerald-900 to-emerald-700 px-6 sm:px-8 py-6 text-white">

                <div class="flex items-start justify-between gap-4">

                    <div class="flex items-center gap-4">

                        <div class="h-12 w-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center">

                            <i
                                data-lucide="file-text"
                                class="w-6 h-6"
                            ></i>

                        </div>


                        <div>

                            <p class="text-[10px] uppercase tracking-[0.18em] text-emerald-200 font-semibold">
                                Demande de devis
                            </p>

                            <h2
                                id="modalReference"
                                class="text-xl sm:text-2xl font-bold mt-1"
                            >
                                DEV-XXXXXXXX
                            </h2>

                            <p
                                id="modalDate"
                                class="text-xs text-emerald-100 mt-1"
                            >
                                Demande reçue le...
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        id="closeModal"
                        class="h-10 w-10 rounded-xl bg-white/10 hover:bg-white/20 border border-white/10 flex items-center justify-center transition"
                    >

                        <i
                            data-lucide="x"
                            class="w-5 h-5"
                        ></i>

                    </button>

                </div>


                <div class="mt-5">

                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 text-xs font-semibold">

                        <span
                            id="modalStatutDot"
                            class="h-2 w-2 rounded-full bg-blue-300"
                        ></span>

                        <span id="modalStatutText">
                            Non traité
                        </span>

                    </span>

                </div>

            </div>


            {{-- BODY --}}
            <div class="overflow-y-auto flex-1 p-6 sm:p-8">

                <div class="space-y-7">


                    {{-- CLIENT --}}
                    <section>

                        <div class="flex items-center gap-2 mb-4">

                            <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">

                                <i
                                    data-lucide="user-round"
                                    class="w-4 h-4"
                                ></i>

                            </div>

                            <div>

                                <h3 class="text-sm font-bold text-slate-900">
                                    Informations du client
                                </h3>

                                <p class="text-[10px] text-slate-400">
                                    Coordonnées du demandeur
                                </p>

                            </div>

                        </div>


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">


                            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">

                                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-2">
                                    Client
                                </p>

                                <div class="flex items-center gap-3">

                                    <div
                                        id="modalClientIcon"
                                        class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center"
                                    >

                                        <i
                                            data-lucide="building-2"
                                            class="w-5 h-5"
                                        ></i>

                                    </div>


                                    <div>

                                        <p
                                            id="modalClientName"
                                            class="text-sm font-bold text-slate-900"
                                        >
                                            PLANETECH
                                        </p>

                                        <p
                                            id="modalClientType"
                                            class="text-[10px] text-emerald-700 font-medium mt-0.5"
                                        >
                                            Société
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">

                                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-2">
                                    Contact
                                </p>

                                <p
                                    id="modalNom"
                                    class="text-sm font-bold text-slate-900"
                                >
                                    Nom du contact
                                </p>

                                <p class="text-[10px] text-slate-400 mt-1">
                                    Personne à contacter
                                </p>

                            </div>


                            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">

                                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-2">
                                    Adresse email
                                </p>

                                <p
                                    id="modalEmail"
                                    class="text-sm font-medium text-slate-700 break-all"
                                >
                                    email@example.com
                                </p>

                            </div>


                            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">

                                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-2">
                                    Téléphone
                                </p>

                                <p
                                    id="modalTelephone"
                                    class="text-sm font-medium text-slate-700"
                                >
                                    +229 00 00 00 00
                                </p>

                            </div>

                        </div>

                    </section>


                    <div class="border-t border-gray-100"></div>


                    {{-- DEMANDE --}}
                    <section>

                        <div class="flex items-center gap-2 mb-4">

                            <div class="h-8 w-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">

                                <i
                                    data-lucide="briefcase-business"
                                    class="w-4 h-4"
                                ></i>

                            </div>

                            <div>

                                <h3 class="text-sm font-bold text-slate-900">
                                    Informations de la demande
                                </h3>

                                <p class="text-[10px] text-slate-400">
                                    Détails du projet ou besoin exprimé
                                </p>

                            </div>

                        </div>


                        <div class="mb-4 rounded-2xl border border-gray-100 bg-gray-50/70 p-4">

                            <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 mb-2">
                                Domaine
                            </p>

                            <span
                                id="modalDomaine"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold"
                            >
                                Informatique
                            </span>

                        </div>


                        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">

                            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center gap-2">

                                <i
                                    data-lucide="message-square-text"
                                    class="w-4 h-4 text-slate-500"
                                ></i>

                                <p class="text-xs font-bold text-slate-700">
                                    Besoins exprimés
                                </p>

                            </div>


                            <div class="p-5">

                                <p
                                    id="modalBesoins"
                                    class="text-sm leading-7 text-slate-700 whitespace-pre-line break-words"
                                ></p>

                            </div>

                        </div>

                    </section>

                </div>

            </div>


            {{-- FOOTER --}}
            <div class="border-t border-gray-100 bg-gray-50/90 px-6 sm:px-8 py-5">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                    <div>

                        <p class="text-xs font-bold text-slate-900">
                            Répondre au client
                        </p>

                        <p class="text-[10px] text-slate-400 mt-0.5">
                            Choisissez une action pour cette demande.
                        </p>

                    </div>


                    <div class="flex items-center gap-2 flex-wrap">


                        {{-- CHIFFRER --}}
                        <a
                            id="modalChiffrer"
                            href="#"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm hover:shadow-md transition"
                        >

                            <i
                                data-lucide="calculator"
                                class="w-4 h-4"
                            ></i>

                            Chiffrer cette demande

                        </a>


                        {{-- VOIR DEVIS --}}
                        <a
                            id="modalVoirDevis"
                            href="#"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hidden inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm hover:shadow-md transition"
                        >

                            <i
                                data-lucide="file-text"
                                class="w-4 h-4"
                            ></i>

                            Voir ce devis

                        </a>


                        {{-- WHATSAPP --}}
                        <a
                            id="modalWhatsapp"
                            href="#"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#25D366] hover:bg-[#20bd5a] text-white text-xs font-bold shadow-sm hover:shadow-md transition"
                        >

                            <i
                                data-lucide="message-circle"
                                class="w-4 h-4"
                            ></i>

                            WhatsApp

                        </a>


                        {{-- EMAIL --}}
                        <a
                            id="modalMail"
                            href="#"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm hover:shadow-md transition"
                        >

                            <i
                                data-lucide="mail"
                                class="w-4 h-4"
                            ></i>

                            Email

                        </a>


                        {{-- FERMER --}}
                        <button
                            type="button"
                            id="closeModalBottom"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-gray-200 hover:bg-gray-100 text-slate-600 text-xs font-semibold transition"
                        >
                            Fermer
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | LUCIDE
    |--------------------------------------------------------------------------
    */

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }


    /*
    |--------------------------------------------------------------------------
    | FORMULAIRE DE FILTRES
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById('devisFilters');

    const statutInput =
        document.getElementById('statutInput');

    const searchInput =
        document.getElementById('searchInput');

    const clientTypeFilter =
        document.getElementById('clientTypeFilter');

    const domainFilter =
        document.getElementById('domainFilter');

    const filterButton =
        document.getElementById('filterButton');

    const advancedFilters =
        document.getElementById('advancedFilters');


    /*
    |--------------------------------------------------------------------------
    | FILTRE STATUT
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.status-filter')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    statutInput.value =
                        this.dataset.status ||
                        'toutes';

                    form.submit();

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | FILTRES AVANCÉS
    |--------------------------------------------------------------------------
    */

    filterButton.addEventListener(
        'click',
        function () {

            advancedFilters.classList.toggle(
                'hidden'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | TYPE CLIENT
    |--------------------------------------------------------------------------
    */

    clientTypeFilter.addEventListener(
        'change',
        function () {

            form.submit();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RECHERCHE
    |--------------------------------------------------------------------------
    */

    let timerRecherche = null;


    searchInput.addEventListener(
        'input',
        function () {

            clearTimeout(
                timerRecherche
            );


            timerRecherche =
                setTimeout(
                    function () {

                        form.submit();

                    },
                    500
                );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | DOMAINE
    |--------------------------------------------------------------------------
    */

    let timerDomaine = null;


    domainFilter.addEventListener(
        'input',
        function () {

            clearTimeout(
                timerDomaine
            );


            timerDomaine =
                setTimeout(
                    function () {

                        form.submit();

                    },
                    600
                );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | MODAL
    |--------------------------------------------------------------------------
    */

    const devisModal =
        document.getElementById(
            'devisModal'
        );


    const closeModal =
        document.getElementById(
            'closeModal'
        );


    const closeModalBottom =
        document.getElementById(
            'closeModalBottom'
        );


    const modalOverlay =
        document.getElementById(
            'modalOverlay'
        );


    const modalReference =
        document.getElementById(
            'modalReference'
        );


    const modalDate =
        document.getElementById(
            'modalDate'
        );


    const modalClientName =
        document.getElementById(
            'modalClientName'
        );


    const modalClientType =
        document.getElementById(
            'modalClientType'
        );


    const modalClientIcon =
        document.getElementById(
            'modalClientIcon'
        );


    const modalNom =
        document.getElementById(
            'modalNom'
        );


    const modalEmail =
        document.getElementById(
            'modalEmail'
        );


    const modalTelephone =
        document.getElementById(
            'modalTelephone'
        );


    const modalDomaine =
        document.getElementById(
            'modalDomaine'
        );


    const modalBesoins =
        document.getElementById(
            'modalBesoins'
        );


    const modalStatutText =
        document.getElementById(
            'modalStatutText'
        );


    const modalStatutDot =
        document.getElementById(
            'modalStatutDot'
        );


    const modalWhatsapp =
        document.getElementById(
            'modalWhatsapp'
        );


    const modalMail =
        document.getElementById(
            'modalMail'
        );


    const modalChiffrer =
        document.getElementById(
            'modalChiffrer'
        );


    const modalVoirDevis =
        document.getElementById(
            'modalVoirDevis'
        );


    /*
    |--------------------------------------------------------------------------
    | OUVRIR MODAL
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.view-devis')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    const data =
                        this.dataset;


                    /*
                    |----------------------------------------------------------------------
                    | RÉFÉRENCE
                    |----------------------------------------------------------------------
                    */

                    modalReference.textContent =
                        data.reference ||
                        'Sans référence';


                    modalDate.textContent =
                        'Demande reçue le ' +
                        (
                            data.date ||
                            'Date inconnue'
                        );


                    /*
                    |----------------------------------------------------------------------
                    | CLIENT
                    |----------------------------------------------------------------------
                    */

                    if (
                        data.type ===
                        'societe'
                    ) {

                        modalClientName.textContent =
                            data.raisonSociale ||
                            'Société';


                        modalClientType.textContent =
                            'Société';


                        modalClientIcon.innerHTML =
                            '<i data-lucide="building-2" class="w-5 h-5"></i>';


                        modalClientIcon.className =
                            'h-10 w-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center';

                    } else {

                        modalClientName.textContent =
                            data.nom ||
                            'Client';


                        modalClientType.textContent =
                            'Personne physique';


                        modalClientIcon.innerHTML =
                            '<i data-lucide="user-round" class="w-5 h-5"></i>';


                        modalClientIcon.className =
                            'h-10 w-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center';

                    }


                    /*
                    |----------------------------------------------------------------------
                    | CONTACT
                    |----------------------------------------------------------------------
                    */

                    modalNom.textContent =
                        data.nom ||
                        'Non renseigné';


                    modalEmail.textContent =
                        data.email ||
                        'Non renseigné';


                    modalTelephone.textContent =
                        (
                            (data.indicatif || '') +
                            ' ' +
                            (data.telephone || '')
                        ).trim() ||
                        'Non renseigné';


                    /*
                    |----------------------------------------------------------------------
                    | DEMANDE
                    |----------------------------------------------------------------------
                    */

                    modalDomaine.textContent =
                        data.domaine ||
                        'Non renseigné';


                    modalBesoins.textContent =
                        data.besoins ||
                        'Aucun besoin renseigné';


                    /*
                    |----------------------------------------------------------------------
                    | STATUT
                    |----------------------------------------------------------------------
                    */

                    const statut =
                        data.statut ||
                        'en_attente';


                    let statutLabel =
                        'Non traité';


                    if (
                        statut ===
                        'en_cours'
                    ) {

                        statutLabel =
                            'En cours';

                    } else if (
                        statut ===
                        'traite'
                    ) {

                        statutLabel =
                            'Traité';

                    } else if (
                        statut ===
                        'annule'
                    ) {

                        statutLabel =
                            'Annulé';

                    }


                    modalStatutText.textContent =
                        statutLabel;


                    modalStatutDot.className =
                        'h-2 w-2 rounded-full';


                    if (
                        statut ===
                        'en_attente'
                    ) {

                        modalStatutDot.classList.add(
                            'bg-blue-300'
                        );

                    } else if (
                        statut ===
                        'en_cours'
                    ) {

                        modalStatutDot.classList.add(
                            'bg-amber-300'
                        );

                    } else if (
                        statut ===
                        'traite'
                    ) {

                        modalStatutDot.classList.add(
                            'bg-emerald-300'
                        );

                    } else if (
                        statut ===
                        'annule'
                    ) {

                        modalStatutDot.classList.add(
                            'bg-red-300'
                        );

                    } else {

                        modalStatutDot.classList.add(
                            'bg-gray-300'
                        );

                    }


                    /*
                    |----------------------------------------------------------------------
                    | DEVİS
                    |----------------------------------------------------------------------
                    */

                    const devisTraite =
                        statut ===
                        'traite';


                    if (devisTraite) {

                        modalChiffrer.classList.add(
                            'hidden'
                        );


                        if (data.devisUrl) {

                            modalVoirDevis.href =
                                data.devisUrl;

                            modalVoirDevis.classList.remove(
                                'hidden'
                            );

                        } else {

                            modalVoirDevis.href =
                                '#';

                            modalVoirDevis.classList.add(
                                'hidden'
                            );

                        }

                    } else {

                        modalChiffrer.classList.remove(
                            'hidden'
                        );


                        modalChiffrer.href =
                            data.chiffrerUrl ||
                            '#';


                        modalVoirDevis.href =
                            '#';


                        modalVoirDevis.classList.add(
                            'hidden'
                        );

                    }


                    /*
                    |----------------------------------------------------------------------
                    | WHATSAPP
                    |----------------------------------------------------------------------
                    */

                    let phone =
                        (data.indicatif || '') +
                        (data.telephone || '');


                    phone =
                        phone
                            .replace(/\s+/g, '')
                            .replace(/-/g, '')
                            .replace('+', '');


                    const whatsappMessage =
                        `Bonjour ${data.nom || 'Monsieur/Madame'},\n\n` +
                        `Nous avons bien reçu votre demande de devis ${data.reference}.\n\n` +
                        `Nous revenons vers vous concernant votre demande.\n\n` +
                        `Cordialement,\nPLANETECH`;


                    modalWhatsapp.href =
                        'https://wa.me/' +
                        phone +
                        '?text=' +
                        encodeURIComponent(
                            whatsappMessage
                        );


                    /*
                    |----------------------------------------------------------------------
                    | EMAIL
                    |----------------------------------------------------------------------
                    */

                    const emailSubject =
                        `Réponse à votre demande de devis ${data.reference}`;


                    const emailBody =
                        `Bonjour ${data.nom || 'Monsieur/Madame'},\n\n` +
                        `Nous avons bien reçu votre demande de devis référencée ${data.reference}.\n\n` +
                        `Nous revenons vers vous concernant votre demande.\n\n` +
                        `Cordialement,\nPLANETECH`;


                    modalMail.href =
                        'mailto:' +
                        (data.email || '') +
                        '?subject=' +
                        encodeURIComponent(
                            emailSubject
                        ) +
                        '&body=' +
                        encodeURIComponent(
                            emailBody
                        );


                    /*
                    |----------------------------------------------------------------------
                    | AFFICHER
                    |----------------------------------------------------------------------
                    */

                    devisModal.classList.remove(
                        'hidden'
                    );


                    devisModal.setAttribute(
                        'aria-hidden',
                        'false'
                    );


                    document.body.classList.add(
                        'overflow-hidden'
                    );


                    if (
                        typeof lucide !==
                        'undefined'
                    ) {

                        lucide.createIcons();

                    }

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | FERMER MODAL
    |--------------------------------------------------------------------------
    */

    function closeDevisModal()
    {
        devisModal.classList.add(
            'hidden'
        );


        devisModal.setAttribute(
            'aria-hidden',
            'true'
        );


        document.body.classList.remove(
            'overflow-hidden'
        );
    }


    closeModal.addEventListener(
        'click',
        closeDevisModal
    );


    closeModalBottom.addEventListener(
        'click',
        closeDevisModal
    );


    modalOverlay.addEventListener(
        'click',
        closeDevisModal
    );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                !devisModal.classList.contains(
                    'hidden'
                )
            ) {

                closeDevisModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | LUCIDE INITIAL
    |--------------------------------------------------------------------------
    */

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

});

</script>

</body>

</html>
