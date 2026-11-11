<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PLANETECH Administration - Entretiens du jour</title>

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
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <i data-lucide="calendar-check" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h1 class="mt-1 text-2xl font-bold text-slate-900">Entretiens du Jour</h1>
                        <p class="mt-1 text-sm capitalize text-slate-500">{{ $dateJour }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-[10px] font-semibold text-slate-500 shadow-sm ring-1 ring-gray-100">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Actualisation automatique
                    </span>

                    <a
                        href="{{ url()->current() }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800"
                    >
                        <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                        Actualiser
                    </a>
                        <a
                            href="{{ route('admin.entretiens.jour.pdf', ['download' => 1]) }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100"
                        >
                            <i data-lucide="download" class="h-4 w-4"></i>
                            Télécharger PDF
                        </a>

                        <button
                            id="btnImprimer"
                            type="button"
                            data-print-url="{{ route('admin.entretiens.jour.pdf') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <i data-lucide="printer" class="print-icon h-4 w-4"></i>
                            <span class="print-spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-slate-600 border-t-transparent"></span>
                            <span class="print-label">Imprimer</span>
                        </button>
                </div>

            </div>

            {{-- STATISTIQUES --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Candidats présents</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalJour }}</p>
                            <p class="mt-1 text-[10px] text-emerald-600">Arrivés aujourd'hui</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <i data-lucide="users" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Première arrivée</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $premiereArrivee ? $premiereArrivee->created_at->format('H:i') : '--:--' }}
                            </p>
                            <p class="mt-1 max-w-[160px] truncate text-[10px] text-blue-500">
                                {{ $premiereArrivee ? $premiereArrivee->nom . ' ' . $premiereArrivee->prenoms : 'Aucune pour le moment' }}
                            </p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <i data-lucide="log-in" class="h-5 w-5"></i>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Dernière arrivée</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $derniereArrivee ? $derniereArrivee->created_at->format('H:i') : '--:--' }}
                            </p>
                            <p class="mt-1 max-w-[160px] truncate text-[10px] text-amber-600">
                                {{ $derniereArrivee ? $derniereArrivee->nom . ' ' . $derniereArrivee->prenoms : 'Aucune pour le moment' }}
                            </p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <i data-lucide="clock-3" class="h-5 w-5"></i>
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
            <h3 class="text-sm font-bold text-slate-900">Ordre d'arrivée</h3>
            <p class="mt-1 text-xs text-slate-400">
                @if($entretiens->count() > 0)
                    <span class="font-semibold text-slate-600">{{ $entretiens->count() }}</span>
                    candidat{{ $entretiens->count() > 1 ? 's' : '' }}
                    {{ $recherche ? 'trouvé' . ($entretiens->count() > 1 ? 's' : '') : 'aujourd\'hui' }}
                @else
                    Aucun candidat pour le moment
                @endif
            </p>
        </div>
    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[800px]">

            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Rang</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Candidat</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Téléphone</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Poste visé</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Attente</th>
                    <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Arrivée</th>
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

                <tr class="transition hover:bg-gray-50 {{ $loop->first && !$recherche ? 'bg-emerald-50/40' : '' }}">

                    <td class="px-6 py-4">
                        <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg px-2 text-xs font-bold
                            {{ $loop->first ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-slate-600' }}">
                            {{ $loop->iteration }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-700">
                                {{ $initiales ?: '?' }}
                            </div>

                            <div class="min-w-0">
                                <p class="max-w-[220px] truncate text-xs font-bold uppercase text-slate-900">
                                    {{ $item->nom }}
                                </p>
                                <p class="max-w-[220px] truncate text-[11px] text-slate-500">
                                    {{ $item->prenoms }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <span class="text-xs font-medium text-slate-700">
                            {{ $item->telephone }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            {{ $item->poste }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        <p class="text-xs text-slate-500">
                            {{ $item->created_at->diffForHumans() }}
                        </p>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700">
                            {{ $item->created_at->format('H:i') }}
                        </span>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">

                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                            <i data-lucide="calendar-x" class="h-6 w-6 text-slate-400"></i>
                        </div>

                        <p class="mt-4 text-sm font-semibold text-slate-700">Aucun candidat aujourd'hui</p>

                        <p class="mt-1 text-xs text-slate-400">
                            Les arrivées enregistrées aujourd'hui apparaîtront ici.
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

</div>

        </main>

    </div>

</div>

<script>
    const formulaire = document.getElementById('filtresEntretiens');
    const champRecherche = document.getElementById('rechercheEntretiens');
    let timerRecherche = null;

    // Recherche automatique après 500 ms sans frappe
    champRecherche.addEventListener('input', function () {
        clearTimeout(timerRecherche);
        timerRecherche = setTimeout(function () {
            formulaire.submit();
        }, 500);
    });

    // Actualisation automatique toutes les 60 s (sauf si l'utilisateur est en train de taper)
    setInterval(function () {
        if (document.activeElement !== champRecherche) {
            window.location.reload();
        }
    }, 60000);

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            lucide.createIcons();
        }
    });

    // Impression directe du PDF du jour (iframe invisible)
    const btnImprimer = document.getElementById('btnImprimer');

    if (btnImprimer) {
        btnImprimer.addEventListener('click', function () {
            const bouton = this;
            const url = bouton.dataset.printUrl + '?t=' + Date.now();

            const icone = bouton.querySelector('.print-icon');
            const spinner = bouton.querySelector('.print-spinner');
            const label = bouton.querySelector('.print-label');

            const finir = function () {
                bouton.disabled = false;
                icone.classList.remove('hidden');
                spinner.classList.add('hidden');
                label.textContent = 'Imprimer';
            };

            bouton.disabled = true;
            icone.classList.add('hidden');
            spinner.classList.remove('hidden');
            label.textContent = 'Préparation...';

            document.getElementById('iframeImpression')?.remove();

            const iframe = document.createElement('iframe');
            iframe.id = 'iframeImpression';
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
            iframe.src = url;

            iframe.onload = function () {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    // Repli : ouvrir le PDF dans un nouvel onglet
                    window.open(url, '_blank');
                }
                finir();
            };

            document.body.appendChild(iframe);
        });
    }
</script>

</body>
</html>
