<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PLANETECH Administration - Registre des entretiens</title>

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

    @includeIf('admin.aside')

    <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">

        <main class="space-y-6 p-6 lg:p-8">

            {{-- HEADER --}}
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i data-lucide="users" class="h-5 w-5"></i>
                </div>

                <div>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">Registre des Entretiens</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Liste des candidats arrivés pour un entretien de recrutement.
                    </p>
                </div>
            </div>

            {{-- STATISTIQUES --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Total arrivées</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalEntretiens }}</p>
                            <p class="mt-1 text-[10px] text-slate-400">Tous les candidats</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <i data-lucide="users" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Aujourd'hui</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $entretiensAujourdhui }}</p>
                            <p class="mt-1 text-[10px] text-blue-500">Arrivées du jour</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <i data-lucide="calendar-check" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">7 derniers jours</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $entretiensSemaine }}</p>
                            <p class="mt-1 text-[10px] text-amber-600">Cette semaine</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <i data-lucide="trending-up" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

            </div>

            {{-- RECHERCHE --}}
            <form
                id="filtresEntretiens"
                method="GET"
                action="{{ url()->current() }}"
                class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
            >
                <div class="relative w-full xl:w-96">
                    <i
                        data-lucide="search"
                        class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    ></i>

                    <input
                        id="rechercheEntretiens"
                        name="recherche"
                        type="text"
                        value="{{ $recherche }}"
                        placeholder="Nom, prénom ou poste..."
                        autocomplete="off"
                        class="w-full rounded-xl border border-gray-200 py-2.5 pl-10 pr-4 text-xs outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                    >
                </div>
            </form>

            {{-- TABLEAU --}}
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">

                <div class="flex flex-col gap-2 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Liste des candidats & arrivées</h3>

                        <p class="mt-1 text-xs text-slate-400">
                            @if($entretiens->total() > 0)
                                Affichage de
                                <span class="font-semibold text-slate-600">{{ $entretiens->firstItem() }}</span>
                                à
                                <span class="font-semibold text-slate-600">{{ $entretiens->lastItem() }}</span>
                                sur
                                <span class="font-semibold text-slate-600">{{ $entretiens->total() }}</span>
                                candidat{{ $entretiens->total() > 1 ? 's' : '' }}
                            @else
                                Aucun candidat trouvé
                            @endif
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[700px]">

                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">#</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Candidat</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Poste visé</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Date d'arrivée</th>
                                <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Heure</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">

                        @forelse($entretiens as $item)

                            @php
                                $nomComplet = trim($item->nom . ' ' . $item->prenoms);

                                $initiales = collect(preg_split('/\s+/', $nomComplet))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($mot) => strtoupper(mb_substr($mot, 0, 1)))
                                    ->implode('');
                            @endphp

                            <tr class="transition hover:bg-gray-50">

                                <td class="px-6 py-4">
                                    <p class="text-xs font-bold text-slate-400">
                                        {{ $entretiens->firstItem() + $loop->index }}
                                    </p>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-700">
                                            {{ $initiales ?: '?' }}
                                        </div>

                                        <div class="min-w-0">
                                            <p class="max-w-[260px] truncate text-xs font-bold uppercase text-slate-900">
                                                {{ $item->nom }}
                                            </p>
                                            <p class="max-w-[260px] truncate text-[11px] text-slate-500">
                                                {{ $item->prenoms }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ $item->poste }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <p class="text-xs font-medium text-slate-700">
                                        {{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ $item->created_at ? $item->created_at->diffForHumans() : '' }}
                                    </p>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700">
                                        {{ $item->created_at ? $item->created_at->format('H:i') : '-' }}
                                    </span>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">

                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                        <i data-lucide="users" class="h-6 w-6 text-slate-400"></i>
                                    </div>

                                    <p class="mt-4 text-sm font-semibold text-slate-700">Aucun enregistrement</p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Aucun candidat ne correspond à votre recherche pour le moment.
                                    </p>

                                    @if($recherche)
                                        <a
                                            href="{{ url()->current() }}"
                                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800"
                                        >
                                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                                            Réinitialiser la recherche
                                        </a>
                                    @endif

                                </td>
                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

                {{-- PAGINATION --}}
                @if($entretiens->hasPages())

                    <div class="border-t border-gray-100 bg-white px-6 py-4">

                        <div class="flex items-center justify-between gap-3">

                            @if($entretiens->onFirstPage())
                                <span class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-400">
                                    Précédent
                                </span>
                            @else
                                <a
                                    href="{{ $entretiens->previousPageUrl() }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    <i data-lucide="chevron-left" class="mr-1 h-4 w-4"></i>
                                    Précédent
                                </a>
                            @endif

                            <span class="text-xs text-slate-500">
                                Page
                                <span class="font-semibold text-slate-700">{{ $entretiens->currentPage() }}</span>
                                sur
                                <span class="font-semibold text-slate-700">{{ $entretiens->lastPage() }}</span>
                            </span>

                            @if($entretiens->hasMorePages())
                                <a
                                    href="{{ $entretiens->nextPageUrl() }}"
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

                @endif

            </div>

        </main>

    </div>

</div>

<script>
    // Recherche automatique après 500 ms sans frappe
    const formulaire = document.getElementById('filtresEntretiens');
    const champRecherche = document.getElementById('rechercheEntretiens');
    let timerRecherche = null;

    champRecherche.addEventListener('input', function () {
        clearTimeout(timerRecherche);
        timerRecherche = setTimeout(function () {
            formulaire.submit();
        }, 500);
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

</body>
</html>
