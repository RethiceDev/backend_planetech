<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PLANETECH Administration - les bons de commandes</title>

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

            {{-- HEADER --}}
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i data-lucide="shopping-cart" class="h-5 w-5"></i>
                </div>

                <div>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">Les Bons de Commandes</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Vérifiez la disponibilité, renseignez les prix et envoyez le devis au client.
                    </p>
                </div>
            </div>

            {{-- STATISTIQUES --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Total commandes</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalCommandes }}</p>
                            <p class="mt-1 text-[10px] text-slate-400">Toutes les commandes</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Nouvelles</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $nouvellesCommandes }}</p>
                            <p class="mt-1 text-[10px] text-blue-500">À chiffrer</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <i data-lucide="clock-3" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">En cours</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $commandesEnCours }}</p>
                            <p class="mt-1 text-[10px] text-amber-600">Devis envoyés / en traitement</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <i data-lucide="loader-circle" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Terminées</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $commandesTerminees }}</p>
                            <p class="mt-1 text-[10px] text-emerald-600">Commandes terminées</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

            </div>

            {{-- FILTRES --}}
            <form
                id="filtresCommandes"
                method="GET"
                action="{{ url()->current() }}"
                class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
            >
                <input type="hidden" name="statut" id="statutFiltre" value="{{ $statut }}">

                <div class="flex flex-col gap-4 xl:flex-row xl:items-center">

                    <div class="flex flex-1 flex-wrap items-center gap-2">

                        @php
                            $filtres = [
                                'toutes' => ['Toutes', $totalCommandes],
                                'nouvelles' => ['Nouvelles', $nouvellesCommandes],
                                'en_cours' => ['En cours', $commandesEnCours],
                                'terminees' => ['Terminées', $commandesTerminees],
                            ];
                        @endphp

                        @foreach($filtres as $cle => [$libelle, $compte])
                            <button
                                type="button"
                                class="filtre-statut rounded-xl px-4 py-2 text-xs font-semibold transition
                                {{ $statut === $cle
                                    ? 'bg-emerald-700 text-white'
                                    : 'border border-gray-200 bg-white text-slate-600 hover:bg-gray-50' }}"
                                data-statut="{{ $cle }}"
                            >
                                {{ $libelle }}
                                <span class="ml-1 opacity-70">{{ $compte }}</span>
                            </button>
                        @endforeach

                    </div>

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

            {{-- TABLEAU --}}
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">

                <div class="flex flex-col gap-2 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Commandes reçues</h3>

                        <p class="mt-1 text-xs text-slate-400">
                            @if($commandes->total() > 0)
                                Affichage de
                                <span class="font-semibold text-slate-600">{{ $commandes->firstItem() }}</span>
                                à
                                <span class="font-semibold text-slate-600">{{ $commandes->lastItem() }}</span>
                                sur
                                <span class="font-semibold text-slate-600">{{ $commandes->total() }}</span>
                                commande{{ $commandes->total() > 1 ? 's' : '' }}
                            @else
                                Aucune commande trouvée
                            @endif
                        </p>
                    </div>

                    <div class="inline-flex w-fit items-center gap-2 rounded-full bg-gray-50 px-3 py-1.5 text-[10px] font-semibold text-slate-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                        @switch($statut)
                            @case('nouvelles') Nouvelles commandes @break
                            @case('en_cours') Commandes en cours @break
                            @case('terminees') Commandes terminées @break
                            @default Toutes les commandes
                        @endswitch
                    </div>

                </div>

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1100px]">

                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Commande</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Client</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Articles</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Montant</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Statut</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Date</th>
                                <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">

                        @forelse($commandes as $commande)

                            @php
                                $nomClient = $commande->type_client === 'entreprise' && $commande->raison_sociale
                                    ? $commande->raison_sociale
                                    : $commande->nom;

                                $initiales = collect(preg_split('/\s+/', trim($nomClient)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($mot) => strtoupper(mb_substr($mot, 0, 1)))
                                    ->implode('');

                                $statutLabel = match ($commande->statut) {
                                    'recue' => 'Nouvelle',
                                    'en_cours' => 'En cours',
                                    'en_traitement' => 'Devis envoyé',
                                    'terminee' => 'Terminée',
                                    'livree' => 'Livrée',
                                    default => ucfirst(str_replace('_', ' ', $commande->statut)),
                                };

                                $statutClasses = match ($commande->statut) {
                                    'recue' => 'bg-blue-50 text-blue-700',
                                    'en_cours', 'en_traitement' => 'bg-amber-50 text-amber-700',
                                    'terminee', 'livree' => 'bg-emerald-50 text-emerald-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };

                                $nombreArticles = $commande->items->sum('quantite');
                                $devisEnvoye = $commande->total !== null;
                            @endphp

                            <tr class="transition hover:bg-gray-50">

                                <td class="px-6 py-4">
                                    <p class="text-xs font-bold text-slate-900">{{ $commande->reference }}</p>
                                    <p class="mt-1 text-[10px] text-slate-400">#{{ $commande->id }}</p>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-700">
                                            {{ $initiales ?: '?' }}
                                        </div>

                                        <div class="min-w-0">
                                            <p class="max-w-[220px] truncate text-xs font-bold text-slate-900">
                                                {{ $nomClient }}
                                            </p>

                                            @if($commande->type_client === 'entreprise' && $commande->raison_sociale)
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

                                <td class="px-4 py-4">
                                    <p class="text-xs font-semibold text-slate-700">
                                        {{ $commande->items->count() }} article(s)
                                    </p>
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $nombreArticles }} unité(s)
                                    </p>
                                </td>

                                <td class="px-4 py-4">
                                    @if($devisEnvoye)
                                        <p class="text-sm font-bold text-slate-900">
                                            {{ number_format((float) $commande->total, 0, ',', ' ') }} FCFA
                                        </p>
                                    @else
                                        <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1.5 text-[10px] font-semibold text-gray-500">
                                            À chiffrer
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[10px] font-semibold {{ $statutClasses }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ $statutLabel }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <p class="text-xs font-medium text-slate-700">
                                        {{ $commande->created_at ? $commande->created_at->format('d/m/Y') : '-' }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $commande->created_at ? $commande->created_at->format('H:i') : '-' }}
                                    </p>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <button
                                        type="button"
                                        onclick="ouvrirCommande({{ $commande->id }})"
                                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        {{ $devisEnvoye ? 'Voir' : 'Chiffrer' }}
                                    </button>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">

                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                        <i data-lucide="shopping-bag" class="h-6 w-6 text-slate-400"></i>
                                    </div>

                                    <p class="mt-4 text-sm font-semibold text-slate-700">Aucune commande</p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Aucune commande ne correspond à vos critères.
                                    </p>

                                    @if($recherche || $statut !== 'toutes')
                                        <a
                                            href="{{ url()->current() }}"
                                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800"
                                        >
                                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                                            Réinitialiser les filtres
                                        </a>
                                    @endif

                                </td>
                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

                {{-- PAGINATION --}}
                @if($commandes->hasPages())

                    <div class="border-t border-gray-100 bg-white px-6 py-4">

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                            <div class="text-xs text-slate-500">
                                Affichage de
                                <span class="font-semibold text-slate-700">{{ $commandes->firstItem() }}</span>
                                à
                                <span class="font-semibold text-slate-700">{{ $commandes->lastItem() }}</span>
                                sur
                                <span class="font-semibold text-slate-700">{{ $commandes->total() }}</span>
                                commande{{ $commandes->total() > 1 ? 's' : '' }}
                            </div>

                            <div class="flex items-center gap-2">

                                @if($commandes->onFirstPage())
                                    <span class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400">
                                        Précédent
                                    </span>
                                @else
                                    <a
                                        href="{{ $commandes->previousPageUrl() }}"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i data-lucide="chevron-left" class="mr-1 h-4 w-4"></i>
                                        Précédent
                                    </a>
                                @endif

                                <div class="hidden items-center gap-1 sm:flex">
                                    @foreach(
                                        $commandes->getUrlRange(
                                            max(1, $commandes->currentPage() - 2),
                                            min($commandes->lastPage(), $commandes->currentPage() + 2)
                                        ) as $page => $url
                                    )
                                        @if($page == $commandes->currentPage())
                                            <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-emerald-700 px-3 text-xs font-bold text-white shadow-sm">
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

                                <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-slate-100 px-3 text-xs font-bold text-slate-700 sm:hidden">
                                    {{ $commandes->currentPage() }}
                                </span>

                                @if($commandes->hasMorePages())
                                    <a
                                        href="{{ $commandes->nextPageUrl() }}"
                                        class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800"
                                    >
                                        Suivant
                                        <i data-lucide="chevron-right" class="ml-1 h-4 w-4"></i>
                                    </a>
                                @else
                                    <span class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-400">
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
     MODAL COMMANDE / DEVIS
============================================================= --}}

<div id="commandeModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">

    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="fermerCommande()"></div>

    <div class="relative flex min-h-full items-center justify-center p-3 sm:p-4">

        <div class="relative flex h-[92vh] w-full max-w-5xl min-h-0 flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">

            {{-- HEADER --}}
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-6 py-5">

                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">
                        Détail de la commande
                    </p>
                    <h2 id="modalReference" class="mt-1 text-xl font-bold text-slate-900">-</h2>
                    <p id="modalCommandeId" class="mt-1 text-xs text-slate-400">-</p>
                </div>

                <button
                    type="button"
                    onclick="fermerCommande()"
                    class="ml-4 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-slate-500 transition hover:bg-gray-200 hover:text-slate-800"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>

            </div>

            {{-- INFOS CLIENT --}}
            <div class="grid shrink-0 grid-cols-1 gap-4 border-b border-gray-100 bg-gray-50 p-5 md:grid-cols-3">

                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Client</p>
                    <p id="modalClient" class="mt-1 text-sm font-bold text-slate-900">-</p>
                    <p id="modalContact" class="mt-1 text-xs text-slate-500">-</p>
                </div>

                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Email</p>
                    <p id="modalEmail" class="mt-1 break-all text-xs text-slate-700">-</p>
                </div>

                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Téléphone</p>
                    <p id="modalTelephone" class="mt-1 text-xs text-slate-700">-</p>
                </div>

            </div>

            {{-- CORPS --}}
            <div class="min-h-0 flex-1 overflow-y-auto">

                <div class="p-5 sm:p-6">

                    <div class="mb-4">
                        <h3 class="text-sm font-bold text-slate-900">Articles commandés</h3>
                        <p id="modalNombreArticles" class="mt-1 text-xs text-slate-400">-</p>
                        <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                            Cochez « Disponible » pour chaque article en stock et saisissez son prix unitaire.
                            Décochez les articles indisponibles.
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-gray-100">
                        <div class="overflow-x-auto">

                            <table class="w-full min-w-[760px]">

                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Désignation</th>
                                        <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">Quantité</th>
                                        <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">Disponible</th>
                                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Prix unitaire (FCFA)</th>
                                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Total</th>
                                    </tr>
                                </thead>

                                <tbody id="modalArticles" class="divide-y divide-gray-100"></tbody>

                            </table>

                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <div class="w-full max-w-sm rounded-2xl bg-emerald-50 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-xs font-semibold text-emerald-800">Total du devis</span>
                                <span id="modalTotal" class="text-lg font-bold text-emerald-900">0 FCFA</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="relative z-20 shrink-0 border-t border-gray-200 bg-white px-4 py-4 shadow-[0_-8px_20px_rgba(15,23,42,0.05)] sm:px-6">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                            <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-700">Envoi du devis</p>
                            <p class="mt-0.5 max-w-md text-[11px] leading-relaxed text-slate-400">
                                Le client recevra par email la disponibilité de chaque article
                                et les prix que vous avez saisis.
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">

                        <button
                            type="button"
                            onclick="fermerCommande()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                            Fermer
                        </button>

                        <button
                            id="btnEnvoyerDevis"
                            type="button"
                            onclick="envoyerDevis()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-6 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Envoyer le devis au client
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>
    window.commandesData = @json($commandesData);
</script>


<script>

    let commandeActuelleId = null;

    const modal = document.getElementById('commandeModal');
    const formulaireFiltres = document.getElementById('filtresCommandes');
    const inputRecherche = document.getElementById('rechercheCommandes');
    const inputStatut = document.getElementById('statutFiltre');
    const boutonsStatut = document.querySelectorAll('.filtre-statut');

    /* FILTRE STATUT */
    boutonsStatut.forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            inputStatut.value = this.dataset.statut || 'toutes';
            formulaireFiltres.submit();
        });
    });

    /* RECHERCHE */
    let timerRecherche = null;

    inputRecherche.addEventListener('input', function () {
        clearTimeout(timerRecherche);
        timerRecherche = setTimeout(function () {
            formulaireFiltres.submit();
        }, 500);
    });

    /* UTILITAIRES */
    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function formatFCFA(nombre) {
        return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 })
            .format(Number(nombre || 0)) + ' FCFA';
    }

    function obtenirTokenCSRF() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    /* RECALCUL DES TOTAUX EN DIRECT */
    function recalculer() {
        let total = 0;

        document.querySelectorAll('#modalArticles tr[data-item-id]').forEach(function (ligne) {
            const quantite = Number(ligne.dataset.quantite || 0);
            const checkbox = ligne.querySelector('.checkbox-disponibilite');
            const input = ligne.querySelector('.input-prix');
            const cellule = ligne.querySelector('.cellule-total');

            const disponible = checkbox.checked;

            input.disabled = !disponible;
            input.classList.toggle('bg-gray-100', !disponible);

            ligne.classList.toggle('bg-red-50/40', !disponible);

            const prix = Number(input.value || 0);

            if (disponible && prix > 0) {
                const totalLigne = prix * quantite;
                total += totalLigne;
                cellule.textContent = formatFCFA(totalLigne);
            } else {
                cellule.textContent = disponible ? '-' : 'Indisponible';
            }
        });

        document.getElementById('modalTotal').textContent = formatFCFA(total);
    }

    /* OUVRIR COMMANDE */
    function ouvrirCommande(id) {
        const commande = window.commandesData[id];

        if (!commande) {
            console.error('Commande introuvable :', id);
            return;
        }

        commandeActuelleId = commande.id;

        const client = commande.type_client === 'entreprise' && commande.raison_sociale
            ? commande.raison_sociale
            : commande.nom;

        const contact = commande.type_client === 'entreprise' && commande.raison_sociale
            ? 'Contact : ' + commande.nom
            : 'Particulier';

        document.getElementById('modalReference').textContent = commande.reference || '-';
        document.getElementById('modalCommandeId').textContent = 'Commande #' + commande.id;
        document.getElementById('modalClient').textContent = client || '-';
        document.getElementById('modalContact').textContent = contact;
        document.getElementById('modalEmail').textContent = commande.email || '-';
        document.getElementById('modalTelephone').textContent = commande.telephone || '-';

        const articles = Array.isArray(commande.items) ? commande.items : [];

        const totalUnites = articles.reduce(function (total, item) {
            return total + Number(item.quantite || 0);
        }, 0);

        document.getElementById('modalNombreArticles').textContent =
            articles.length + ' article(s) • ' + totalUnites + ' unité(s)';

        const conteneur = document.getElementById('modalArticles');

        if (articles.length === 0) {
            conteneur.innerHTML = `
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-xs text-slate-400">
                        Aucun article enregistré pour cette commande.
                    </td>
                </tr>
            `;
        } else {
            conteneur.innerHTML = articles.map(function (item) {

                // Par défaut : disponible, sauf si l'admin a déjà indiqué le contraire.
                const disponible = item.disponible !== false;

                const prix = item.prix_unitaire !== null && item.prix_unitaire !== undefined
                    ? item.prix_unitaire
                    : '';

                return `
                    <tr
                        data-item-id="${Number(item.id)}"
                        data-quantite="${Number(item.quantite)}"
                        class="transition"
                    >

                        <td class="px-4 py-4">
                            <p class="text-xs font-semibold text-slate-800">
                                ${escapeHtml(item.designation)}
                            </p>
                        </td>

                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-gray-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                ${escapeHtml(item.quantite)}
                            </span>
                        </td>

                        <td class="px-4 py-4 text-center">
                            <input
                                type="checkbox"
                                class="checkbox-disponibilite h-4 w-4 cursor-pointer rounded border-gray-300 accent-emerald-600"
                                ${disponible ? 'checked' : ''}
                            >
                        </td>

                        <td class="px-4 py-4 text-right">
                            <input
                                type="number"
                                min="0"
                                step="any"
                                inputmode="decimal"
                                value="${prix}"
                                placeholder="0"
                                class="input-prix w-36 rounded-lg border border-gray-200 px-3 py-2 text-right text-xs outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                            >
                        </td>

                        <td class="cellule-total px-4 py-4 text-right text-xs font-bold text-slate-900">
                            -
                        </td>

                    </tr>
                `;
            }).join('');

            conteneur.querySelectorAll('.checkbox-disponibilite, .input-prix').forEach(function (el) {
                el.addEventListener('input', recalculer);
                el.addEventListener('change', recalculer);
            });
        }

        recalculer();

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        if (window.lucide) {
            lucide.createIcons();
        }
    }

    /* FERMER COMMANDE */
    function fermerCommande() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        commandeActuelleId = null;
    }

    /* ENVOYER LE DEVIS */
    async function envoyerDevis() {
        if (!commandeActuelleId) {
            return;
        }

        const lignes = document.querySelectorAll('#modalArticles tr[data-item-id]');

        if (!lignes.length) {
            afficherNotification('Aucun article à traiter.', 'error');
            return;
        }

        const items = [];

        for (const ligne of lignes) {
            const disponible = ligne.querySelector('.checkbox-disponibilite').checked;
            const prix = Number(ligne.querySelector('.input-prix').value || 0);

            if (disponible && prix <= 0) {
                afficherNotification(
                    'Renseignez un prix pour chaque article disponible (ou décochez-le).',
                    'error'
                );
                ligne.querySelector('.input-prix').focus();
                return;
            }

            items.push({
                id: Number(ligne.dataset.itemId),
                disponible: disponible,
                prix_unitaire: disponible ? prix : null
            });
        }

        const bouton = document.getElementById('btnEnvoyerDevis');
        const texteOriginal = bouton.innerHTML;

        bouton.disabled = true;
        bouton.innerHTML = `
            <i data-lucide="loader-circle" class="h-4 w-4 animate-spin"></i>
            Envoi...
        `;

        if (window.lucide) {
            lucide.createIcons();
        }

        try {
            const response = await fetch(`/commande/${commandeActuelleId}/devis`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': obtenirTokenCSRF(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ items: items })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Impossible d\u2019envoyer le devis.');
            }

            fermerCommande();

            afficherNotification(
                data.message || 'Le devis a été envoyé au client.',
                'success'
            );

            setTimeout(function () {
                window.location.reload();
            }, 1200);

        } catch (error) {
            console.error('Erreur envoi devis :', error);

            afficherNotification(
                error.message || 'Impossible d\u2019envoyer le devis.',
                'error'
            );

            bouton.disabled = false;
            bouton.innerHTML = texteOriginal;

            if (window.lucide) {
                lucide.createIcons();
            }
        }
    }

    /* NOTIFICATION */
    function afficherNotification(message, type = 'success') {
        const ancienne = document.getElementById('notificationCommande');

        if (ancienne) {
            ancienne.remove();
        }

        const notification = document.createElement('div');
        notification.id = 'notificationCommande';

        const estSucces = type === 'success';

        notification.className = `
            fixed right-5 top-5 z-[100]
            flex max-w-md items-start gap-3
            rounded-2xl border px-5 py-4 shadow-2xl bg-white
            ${estSucces ? 'border-emerald-200' : 'border-red-200'}
        `;

        notification.innerHTML = `
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${
                estSucces ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
            }">
                <i data-lucide="${estSucces ? 'check-circle-2' : 'circle-alert'}" class="h-5 w-5"></i>
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold ${estSucces ? 'text-emerald-800' : 'text-red-800'}">
                    ${estSucces ? 'Opération réussie' : 'Erreur'}
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
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        `;

        document.body.appendChild(notification);

        if (window.lucide) {
            lucide.createIcons();
        }

        setTimeout(function () {
            notification.remove();
        }, 5000);
    }

    /* ESC */
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            fermerCommande();
        }
    });

    /* INIT */
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            lucide.createIcons();
        }
    });

</script>

</body>
</html>
