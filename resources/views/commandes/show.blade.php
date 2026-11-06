<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PLANETECH Administration - Dossier client {{ $commande->reference }}</title>

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

@php
    // ---- État du dossier (sert à guider l'utilisateur) ----
    $bcOk      = !empty($commande->numero_bc);
    $cloture   = $commande->statut === 'cloturee';
    $nbBL      = $commande->livraisons->count();
    $toutLivre = $bcOk && $commande->items->sum('quantite_restante') <= 0;
    $peutCloturer = $bcOk && $nbBL > 0;

    // ---- Montants ----
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ') . ' FCFA';
    $totalBc = $commande->items->sum(fn ($i) => (float) $i->quantite * (float) $i->prix_unitaire);

    $etapes = [
        ['n' => 1, 'label' => 'Commande',        'etat' => 'fait'],
        ['n' => 2, 'label' => 'Bon de commande', 'etat' => $cloture || $bcOk ? 'fait' : 'actuel'],
        ['n' => 3, 'label' => 'Livraison',       'etat' => $cloture || $toutLivre ? 'fait' : ($bcOk ? 'actuel' : 'bloque')],
        ['n' => 4, 'label' => 'Clôture',         'etat' => $cloture ? 'fait' : ($peutCloturer ? 'actuel' : 'bloque')],
    ];

    $classeStatut = match ($commande->statut) {
        'cloturee' => 'bg-emerald-50 text-emerald-700',
        'livree'   => 'bg-blue-50 text-blue-600',
        default    => 'bg-amber-50 text-amber-600',
    };

    $classeChamp = 'w-full rounded-xl border border-gray-200 px-4 py-2.5 text-xs outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100';
    $classeLabel = 'mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-500';
    $classeBouton = 'inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-xs font-semibold text-white transition hover:bg-emerald-800';
@endphp

<div class="flex h-screen overflow-hidden">

    @includeIf('admin.aside')

    <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">

        <main class="mx-auto w-full max-w-4xl space-y-6 p-6 lg:p-8">

            {{-- ======================= EN-TÊTE ======================= --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <i data-lucide="folder-open" class="h-5 w-5"></i>
                    </div>

                    <div class="min-w-0">
                        <h1 class="mt-1 truncate text-2xl font-bold text-slate-900">
                            {{ $commande->raison_sociale ?? $commande->nom }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Commande réf. <span class="font-semibold text-slate-700">{{ $commande->reference }}</span>
                        </p>
                    </div>
                </div>

                <span class="inline-flex items-center gap-1.5 self-start rounded-lg px-3 py-1.5 text-[11px] font-semibold sm:self-auto {{ $classeStatut }}">
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                    {{ ucfirst(str_replace('_', ' ', $commande->statut)) }}
                </span>

            </div>

            {{-- ======================= MESSAGES ======================= --}}
            @if(session('success'))
                <div class="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-xs text-emerald-800">
                    <i data-lucide="check-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="flex items-start gap-3 rounded-xl border border-red-100 bg-red-50 p-4 text-xs text-red-700">
                    <i data-lucide="alert-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="flex items-start gap-3 rounded-xl border border-red-100 bg-red-50 p-4 text-xs text-red-700">
                    <i data-lucide="alert-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <ul class="space-y-1">
                        @foreach($errors->all() as $erreur)
                            <li>{{ $erreur }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ======================= PROGRESSION ======================= --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <ol class="grid grid-cols-4 gap-2">
                    @foreach($etapes as $etape)
                        @php
                            $cercle = match ($etape['etat']) {
                                'fait'   => 'bg-emerald-700 text-white',
                                'actuel' => 'bg-emerald-100 text-emerald-700 ring-2 ring-emerald-600',
                                default  => 'bg-gray-100 text-slate-400',
                            };
                            $texte = match ($etape['etat']) {
                                'fait'   => 'text-slate-900',
                                'actuel' => 'text-emerald-700',
                                default  => 'text-slate-400',
                            };
                        @endphp

                        <li class="flex flex-col items-center gap-2 text-center">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold {{ $cercle }}">
                                @if($etape['etat'] === 'fait')
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                @elseif($etape['etat'] === 'bloque')
                                    <i data-lucide="lock" class="h-3.5 w-3.5"></i>
                                @else
                                    {{ $etape['n'] }}
                                @endif
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider sm:text-[11px] {{ $texte }}">
                                {{ $etape['label'] }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- ======================= ÉTAPE 2 : BON DE COMMANDE ======================= --}}
            <section id="etape-bc" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">

                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <i data-lucide="file-text" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Étape 2 · Bon de commande (BC)</h2>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ $bcOk ? 'BC enregistré, quantités verrouillées.' : 'Saisissez le BC du client et validez les quantités.' }}
                            </p>
                        </div>
                    </div>

                    @if($bcOk)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            Validé
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 text-[10px] font-semibold text-amber-600">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            À faire
                        </span>
                    @endif
                </div>

                @if(!$bcOk)

                    <form
                        action="{{ route('commandes.enregistrer-bc', $commande->id) }}"
                        method="POST"
                        class="space-y-5"
                        onsubmit="return confirm('Enregistrer le BC ? Les quantités seront verrouillées.');"
                    >
                        @csrf

                        {{-- Infos du BC --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="{{ $classeLabel }}">
                                    Numéro de BC client <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="numero_bc" required placeholder="Ex : BC-2026-089" class="{{ $classeChamp }}">
                            </div>

                            <div>
                                <label class="{{ $classeLabel }}">
                                    Date du BC <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="date_bc" value="{{ date('Y-m-d') }}" required class="{{ $classeChamp }}">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="{{ $classeLabel }}">Observation (optionnel)</label>
                                <input type="text" name="observation_bc" placeholder="Ex : le client a refusé l'article X" class="{{ $classeChamp }}">
                            </div>
                        </div>

                        {{-- Quantités et prix validés --}}
                        <div>
                            <p class="mb-2 text-xs text-slate-500">
                                Ajustez les <span class="font-semibold text-slate-700">quantités</span> et les
                                <span class="font-semibold text-slate-700">prix</span> acceptés par le client.
                                Mettez <span class="font-semibold text-slate-700">0</span> en quantité pour un article refusé.
                            </p>

                            <div class="overflow-x-auto rounded-xl border border-gray-100">
                                <table class="w-full min-w-[640px]">
                                    <thead>
                                        <tr class="border-b border-gray-100 bg-gray-50">
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Article</th>
                                            <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Proforma</th>
                                            <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Qté validée</th>
                                            <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Prix unitaire</th>
                                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Total</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($commande->items as $index => $item)
                                            <tr class="bc-ligne transition hover:bg-gray-50">
                                                <td class="px-4 py-3 text-xs font-medium text-slate-900">{{ $item->designation }}</td>

                                                <td class="px-3 py-3 text-right">
                                                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700">
                                                        {{ $item->quantite }}
                                                    </span>
                                                </td>

                                                <td class="px-3 py-3 text-right">
                                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                    <input
                                                        type="number"
                                                        name="items[{{ $index }}][quantite]"
                                                        min="0"
                                                       
                                                        value="{{ $item->quantite }}"
                                                        required
                                                        class="bc-qte w-20 rounded-xl border border-gray-200 px-3 py-2 text-right text-xs outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                                    >
                                                </td>

                                                <td class="px-3 py-3 text-right">
                                                    <input
                                                        type="number"
                                                        name="items[{{ $index }}][prix_unitaire]"
                                                        min="0"
                                                        step="any"
                                                        value="{{ $item->prix_unitaire }}"
                                                        required
                                                        class="bc-prix w-28 rounded-xl border border-gray-200 px-3 py-2 text-right text-xs outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                                    >
                                                </td>

                                                <td class="bc-total whitespace-nowrap px-4 py-3 text-right text-xs font-semibold text-slate-900">
                                                    {{ $fmt($item->quantite * $item->prix_unitaire) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                    <tfoot>
                                        <tr class="border-t border-gray-100 bg-gray-50">
                                            <td colspan="4" class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                Total du BC
                                            </td>
                                            <td id="totalBc" class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-emerald-700">
                                                {{ $fmt($totalBc) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <button type="submit" class="{{ $classeBouton }}">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Enregistrer le BC et verrouiller les quantités
                        </button>
                    </form>

                @else

                    <div class="space-y-4">

                        <div class="space-y-3 rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="text-slate-400">N° de BC</span>
                                <span class="font-semibold text-slate-900">{{ $commande->numero_bc }}</span>
                            </div>

                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="text-slate-400">Date</span>
                                <span class="font-semibold text-slate-900">{{ $commande->date_bc?->format('d/m/Y') }}</span>
                            </div>

                            @if($commande->observation_bc)
                                <div class="flex items-start justify-between gap-3 text-xs">
                                    <span class="text-slate-400">Observation</span>
                                    <span class="text-right font-medium text-slate-700">{{ $commande->observation_bc }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-100">
                            <table class="w-full min-w-[480px]">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Article</th>
                                        <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Qté</th>
                                        <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Prix unitaire</th>
                                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Total</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    @foreach($commande->items as $item)
                                        <tr>
                                            <td class="px-4 py-3 text-xs font-medium text-slate-900">{{ $item->designation }}</td>
                                            <td class="px-3 py-3 text-right text-xs text-slate-700">{{ $item->quantite }}</td>
                                            <td class="whitespace-nowrap px-3 py-3 text-right text-xs text-slate-700">{{ $fmt($item->prix_unitaire) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold text-slate-900">
                                                {{ $fmt($item->quantite * $item->prix_unitaire) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr class="border-t border-gray-100 bg-gray-50">
                                        <td colspan="3" class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Total du BC
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-emerald-700">
                                            {{ $fmt($totalBc) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>

                @endif

            </section>

            {{-- ======================= ÉTAPE 3 : LIVRAISON ======================= --}}
            <section id="etape-livraison" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm {{ !$bcOk ? 'opacity-60' : '' }}">

                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <i data-lucide="truck" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Étape 3 · Livraison (BL)</h2>
                            <p class="mt-0.5 text-xs text-slate-400">Indiquez ce qui est livré, un BL est généré à chaque livraison.</p>
                        </div>
                    </div>

                    @if($toutLivre)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            Tout livré
                        </span>
                    @endif
                </div>

                @if(!$bcOk)

                    <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-xs text-amber-700">
                        <i data-lucide="lock" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <p>Enregistrez d'abord le bon de commande (étape 2) pour débloquer les livraisons.</p>
                    </div>

                @elseif($toutLivre)

                    <div class="flex items-start gap-3 rounded-xl bg-emerald-50 p-4 text-xs text-emerald-700">
                        <i data-lucide="check-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <p>Tous les articles ont été livrés. Vous pouvez passer à la clôture.</p>
                    </div>

                @else

                    <form action="{{ route('livraisons.store', $commande->id) }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="flex justify-end">
                            <button
                                type="button"
                                id="toutLivrer"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                <i data-lucide="package-check" class="h-4 w-4"></i>
                                Tout livrer
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-100">
                            <table class="w-full min-w-[420px]">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50">
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Article</th>
                                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Reste</th>
                                        <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Livrer</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    @foreach($commande->items as $index => $item)
                                        <tr class="transition hover:bg-gray-50">
                                            <td class="px-4 py-3 text-xs font-medium text-slate-900">{{ $item->designation }}</td>

                                            <td class="px-4 py-3 text-right">
                                                <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700">
                                                    {{ $item->quantite_restante }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 text-right">
                                                <input type="hidden" name="items[{{ $index }}][commande_item_id]" value="{{ $item->id }}">
                                                <input
                                                    type="number"
                                                    name="items[{{ $index }}][quantite_livree]"
                                                    min="0"
                                                    max="{{ $item->quantite_restante }}"
                                                    value="0"
                                                    class="qte-livrer w-20 rounded-xl border border-gray-200 px-3 py-2 text-right text-xs outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <input
                            type="text"
                            name="observation"
                            placeholder="Observation livraison (ex : remis au gardien)"
                            class="{{ $classeChamp }}"
                        >

                        <button type="submit" class="{{ $classeBouton }}">
                            <i data-lucide="file-check" class="h-4 w-4"></i>
                            Générer le bordereau de livraison (BL)
                        </button>
                    </form>

                @endif

                {{-- Historique des BL --}}
                @if($nbBL > 0)
                    <div class="mt-6 border-t border-gray-100 pt-5">
                        <h3 class="mb-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            BL émis ({{ $nbBL }})
                        </h3>

                        <div class="divide-y divide-gray-100 rounded-xl border border-gray-100">
                            @foreach($commande->livraisons as $bl)
                                <div class="flex flex-col gap-3 px-4 py-3 transition hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between">

                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                            <i data-lucide="file-text" class="h-4 w-4"></i>
                                        </div>

                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-slate-900">{{ $bl->numero_bl }}</p>
                                            <p class="mt-0.5 text-[11px] text-slate-500">
                                                {{ $bl->date_livraison->format('d/m/Y') }}
                                                @if($bl->observation)
                                                    <span class="text-slate-300">•</span> {{ $bl->observation }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <a
                                        href="#"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i data-lucide="download" class="h-4 w-4"></i>
                                        PDF
                                    </a>

                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </section>

            {{-- ======================= ÉTAPE 4 : CLÔTURE ======================= --}}
            <section id="etape-cloture" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm {{ !$peutCloturer && !$cloture ? 'opacity-60' : '' }}">

                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <i data-lucide="receipt" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Étape 4 · Clôture & facturation</h2>
                            <p class="mt-0.5 text-xs text-slate-400">Dernière étape : saisissez la facture définitive.</p>
                        </div>
                    </div>
                </div>

                @if($cloture)

                    <div class="flex flex-col gap-4 rounded-xl border border-emerald-100 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1 text-xs text-emerald-800">
                            <p>
                                <span class="text-emerald-700/70">Facture n° :</span>
                                <span class="font-semibold">{{ $commande->reference_facture }}</span>
                            </p>
                            <p>
                                <span class="text-emerald-700/70">Délai de paiement :</span>
                                <span class="font-semibold">{{ $commande->delai_paiement }}</span>
                            </p>
                        </div>

                        <span class="inline-flex items-center gap-1.5 self-start rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 sm:self-auto">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            Dossier 100 % clôturé
                        </span>
                    </div>

                @elseif(!$peutCloturer)

                    <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-xs text-amber-700">
                        <i data-lucide="lock" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <p>La clôture sera disponible après l'enregistrement du BC et l'émission d'au moins un bordereau de livraison.</p>
                    </div>

                @else

                    <form
                        action="{{ route('commandes.cloturer', $commande->id) }}"
                        method="POST"
                        class="space-y-4"
                        onsubmit="return confirm('Clôturer définitivement ce dossier ? Cette action est irréversible.');"
                    >
                        @csrf

                        <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50 p-4 text-xs">
                            <span class="text-slate-400">Montant total du BC</span>
                            <span class="text-sm font-bold text-emerald-700">{{ $fmt($totalBc) }}</span>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="{{ $classeLabel }}">
                                    Référence de la facture <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="reference_facture" required placeholder="Ex : FAC-2026-001" class="{{ $classeChamp }}">
                            </div>

                            <div>
                                <label class="{{ $classeLabel }}">
                                    Délai de paiement <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="delai_paiement" required placeholder="Ex : 30 jours fin de mois" class="{{ $classeChamp }}">
                            </div>
                        </div>

                        <button type="submit" class="{{ $classeBouton }}">
                            <i data-lucide="lock" class="h-4 w-4"></i>
                            Clôturer définitivement le dossier
                        </button>
                    </form>

                @endif

            </section>

        </main>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            lucide.createIcons();
        }

        // Total du BC en direct (quantité x prix)
        const lignesBc = document.querySelectorAll('.bc-ligne');
        const totalBcEl = document.getElementById('totalBc');
        const formater = function (n) {
            return Math.round(n).toLocaleString('fr-FR') + ' FCFA';
        };

        function recalculerBc() {
            let total = 0;
            lignesBc.forEach(function (ligne) {
                const qte = parseFloat(ligne.querySelector('.bc-qte').value) || 0;
                const prix = parseFloat(ligne.querySelector('.bc-prix').value) || 0;
                const sousTotal = qte * prix;
                ligne.querySelector('.bc-total').textContent = formater(sousTotal);
                total += sousTotal;
            });
            if (totalBcEl) {
                totalBcEl.textContent = formater(total);
            }
        }

        lignesBc.forEach(function (ligne) {
            ligne.querySelector('.bc-qte').addEventListener('input', recalculerBc);
            ligne.querySelector('.bc-prix').addEventListener('input', recalculerBc);
        });

        // Bouton "Tout livrer" : remplit chaque champ avec le maximum possible
        const boutonToutLivrer = document.getElementById('toutLivrer');
        if (boutonToutLivrer) {
            boutonToutLivrer.addEventListener('click', function () {
                document.querySelectorAll('.qte-livrer').forEach(function (champ) {
                    champ.value = champ.max;
                });
            });
        }
    });
</script>

</body>
</html>
