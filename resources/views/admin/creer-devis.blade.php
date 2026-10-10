<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chiffrer le devis - PLANETECH</title>

    {{-- Tailwind CSS --}}
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
                        }
                    }
                }
            }
        }
    </script>

    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-50 text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- ===================================================== --}}
    {{-- SIDEBAR --}}
    {{-- ===================================================== --}}

    @include('admin.aside')


    {{-- ===================================================== --}}
    {{-- CONTENU PRINCIPAL --}}
    {{-- ===================================================== --}}

    <main class="flex-1 min-w-0 overflow-y-auto">

        <div class="p-6 lg:p-8 space-y-6">

            {{-- ================================================= --}}
            {{-- HEADER --}}
            {{-- ================================================= --}}

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                <div>

                    <div class="flex items-center gap-2 mb-2">

                        <a
                            href="{{ route('admin.devis') }}"
                            class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-emerald-700 transition"
                        >
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Retour aux demandes
                        </a>

                    </div>

                    <h1 class="text-2xl font-bold text-slate-900">
                        Chiffrer le devis
                    </h1>

                    <p class="text-sm text-slate-500 mt-1">
                        Préparez le devis commercial à partir de la demande du client.
                    </p>

                </div>

                <div class="flex items-center gap-2">

                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-50 text-blue-700 border border-blue-100 text-xs font-semibold">

                        <i data-lucide="file-text" class="w-4 h-4"></i>

                        {{ $demande->reference }}

                    </span>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- MESSAGES --}}
            {{-- ================================================= --}}

            @if(session('success'))

                <div class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">

                    <i data-lucide="check-circle" class="w-5 h-5 mt-0.5 shrink-0"></i>

                    <div class="text-sm">
                        {{ session('success') }}
                    </div>

                </div>

            @endif


            @if(session('error'))

                <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800">

                    <i data-lucide="circle-alert" class="w-5 h-5 mt-0.5 shrink-0"></i>

                    <div class="text-sm">
                        {{ session('error') }}
                    </div>

                </div>

            @endif


            @if($errors->any())

                <div class="p-4 rounded-xl bg-red-50 border border-red-200">

                    <div class="flex items-start gap-3">

                        <i
                            data-lucide="circle-alert"
                            class="w-5 h-5 text-red-600 mt-0.5 shrink-0"
                        ></i>

                        <div>

                            <p class="text-sm font-bold text-red-800 mb-2">
                                Veuillez corriger les erreurs suivantes :
                            </p>

                            <ul class="list-disc list-inside text-xs text-red-700 space-y-1">

                                @foreach($errors->all() as $error)

                                    <li>
                                        {{ $error }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    </div>

                </div>

            @endif


            {{-- ================================================= --}}
            {{-- FORMULAIRE --}}
            {{-- ================================================= --}}

            <form
                id="quoteForm"
                action="{{ route('admin.devis.store', ['demandeDevis' => $demande->id]) }}"
                method="POST"
            >

                @csrf


                {{-- ================================================= --}}
                {{-- INFORMATIONS CLIENT --}}
                {{-- ================================================= --}}

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">

                    <div class="px-6 py-4 border-b border-gray-100">

                        <div class="flex items-center gap-3">

                            <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">

                                <i data-lucide="user-round" class="w-5 h-5"></i>

                            </div>

                            <div>

                                <h2 class="text-sm font-bold text-slate-900">
                                    Informations du client
                                </h2>

                                <p class="text-xs text-slate-400">
                                    Informations provenant de la demande
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="p-6">

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                            {{-- CLIENT --}}

                            <div>

                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Client
                                </p>

                                <p class="text-sm font-bold text-slate-900 mt-1">

                                    @if($demande->type_client === 'societe')

                                        {{ $demande->raison_sociale }}

                                    @else

                                        {{ $demande->nom }}

                                    @endif

                                </p>

                            </div>


                            {{-- CONTACT --}}

                            <div>

                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Contact
                                </p>

                                <p class="text-sm font-medium text-slate-700 mt-1">
                                    {{ $demande->nom }}
                                </p>

                            </div>


                            {{-- EMAIL --}}

                            <div>

                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Email
                                </p>

                                <p class="text-sm font-medium text-slate-700 mt-1 break-all">
                                    {{ $demande->email }}
                                </p>

                            </div>


                            {{-- TELEPHONE --}}

                            <div>

                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Téléphone
                                </p>

                                <p class="text-sm font-medium text-slate-700 mt-1">
                                    {{ $demande->indicatif }}
                                    {{ $demande->telephone }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- DEMANDE CLIENT --}}
                {{-- ================================================= --}}

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6">

                    <div class="px-6 py-4 border-b border-gray-100">

                        <h2 class="text-sm font-bold text-slate-900">
                            Demande du client
                        </h2>

                    </div>


                    <div class="p-6">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            {{-- DOMAINE --}}

                            <div>

                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Domaine
                                </p>

                                <span class="inline-flex mt-2 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold">
                                    {{ $demande->domaine }}
                                </span>

                            </div>


                            {{-- REFERENCE --}}

                            <div>

                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
                                    Référence de la demande
                                </p>

                                <p class="text-sm font-bold text-slate-900 mt-1">
                                    {{ $demande->reference }}
                                </p>

                            </div>

                        </div>


                        {{-- BESOIN --}}

                        <div class="mt-5">

                            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-2">
                                Besoin exprimé
                            </p>

                            <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">

                                <p class="text-sm leading-7 text-slate-700 whitespace-pre-line">
                                    {{ $demande->besoins }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- INFORMATIONS DU DEVIS --}}
                {{-- ================================================= --}}

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6">

                    <div class="px-6 py-4 border-b border-gray-100">

                        <div class="flex items-center gap-3">

                            <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">

                                <i data-lucide="file-pen-line" class="w-5 h-5"></i>

                            </div>

                            <div>

                                <h2 class="text-sm font-bold text-slate-900">
                                    Informations du devis
                                </h2>

                                <p class="text-xs text-slate-400">
                                    Définissez les informations commerciales du devis.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="p-6">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                            {{-- NUMERO AUTOMATIQUE --}}

                            <div>

                                <label
                                    for="numero_affichage"
                                    class="block text-xs font-semibold text-slate-700 mb-2"
                                >
                                    Numéro du devis
                                </label>

                                <div class="relative">

                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-emerald-600">
                                        <i data-lucide="wand-sparkles" class="w-4 h-4"></i>
                                    </span>

                                    <input
                                        type="text"
                                        id="numero_affichage"
                                        value="Généré automatiquement"
                                        readonly
                                        class="w-full pl-11 pr-4 py-3 rounded-xl border border-emerald-100 bg-emerald-50/50 text-sm text-emerald-700 font-semibold cursor-not-allowed"
                                    >

                                </div>

                                <p class="text-[11px] text-slate-400 mt-2">
                                    Le numéro unique sera attribué automatiquement lors de l'enregistrement.
                                </p>

                            </div>


                            {{-- DATE EMISSION --}}

                            <div>

                                <label
                                    for="date_emission"
                                    class="block text-xs font-semibold text-slate-700 mb-2"
                                >
                                    Date d'émission
                                </label>

                                <input
                                    type="date"
                                    id="date_emission"
                                    name="date_emission"
                                    value="{{ old('date_emission', date('Y-m-d')) }}"
                                    required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                >

                            </div>


                            {{-- DATE VALIDITE --}}

                            <div>

                                <label
                                    for="date_validite"
                                    class="block text-xs font-semibold text-slate-700 mb-2"
                                >
                                    Valable jusqu'au
                                </label>

                                <input
                                    type="date"
                                    id="date_validite"
                                    name="date_validite"
                                    value="{{ old('date_validite', date('Y-m-d', strtotime('+30 days'))) }}"
                                    required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- LIGNES DU DEVIS --}}
                {{-- ================================================= --}}

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">

                    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                        <div>

                            <h2 class="text-sm font-bold text-slate-900">
                                Prestations et produits
                            </h2>

                            <p class="text-xs text-slate-400 mt-0.5">
                                Ajoutez les éléments à facturer.
                            </p>

                        </div>


                        <button
                            type="button"
                            id="addLine"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition"
                        >

                            <i data-lucide="plus" class="w-4 h-4"></i>

                            Ajouter une ligne

                        </button>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[800px]">

                            <thead>

                                <tr class="bg-gray-50 border-b border-gray-100">

                                    <th class="text-left px-6 py-3 text-[10px] uppercase tracking-wider text-slate-500 font-bold">
                                        Désignation
                                    </th>

                                    <th class="text-center px-4 py-3 text-[10px] uppercase tracking-wider text-slate-500 font-bold w-32">
                                        Quantité
                                    </th>

                                    <th class="text-right px-4 py-3 text-[10px] uppercase tracking-wider text-slate-500 font-bold w-44">
                                        Prix unitaire
                                    </th>

                                    <th class="text-right px-4 py-3 text-[10px] uppercase tracking-wider text-slate-500 font-bold w-44">
                                        Total
                                    </th>

                                    <th class="w-14"></th>

                                </tr>

                            </thead>


                            <tbody id="linesBody">

                                {{-- PREMIERE LIGNE --}}

                                <tr class="quote-line border-b border-gray-100">

                                    {{-- DESIGNATION --}}

                                    <td class="px-6 py-4">

                                        <input
                                            type="text"
                                            name="lignes[0][designation]"
                                            value="{{ old('lignes.0.designation') }}"
                                            placeholder="Ex. Développement du site web"
                                            required
                                            class="designation w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                        >

                                    </td>


                                    {{-- QUANTITE --}}

                                    <td class="px-4 py-4">

                                        <input
                                            type="number"
                                            name="lignes[0][quantite]"
                                            value="{{ old('lignes.0.quantite', 1) }}"
                                            min="1"
                                            step="1"
                                            required
                                            class="quantity w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs text-center focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                        >

                                    </td>


                                    {{-- PRIX UNITAIRE --}}

                                    <td class="px-4 py-4">

                                        <input
                                            type="number"
                                            name="lignes[0][prix_unitaire]"
                                            value="{{ old('lignes.0.prix_unitaire', 0) }}"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="unit-price w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs text-right focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                        >

                                    </td>


                                    {{-- TOTAL LIGNE --}}

                                    <td class="px-4 py-4 text-right">

                                        <span class="line-total text-sm font-bold text-slate-900">
                                            0 FCFA
                                        </span>

                                    </td>


                                    {{-- SUPPRIMER --}}

                                    <td class="px-4 py-4 text-center">

                                        <button
                                            type="button"
                                            class="remove-line h-8 w-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition"
                                            title="Supprimer la ligne"
                                        >

                                            <i data-lucide="trash-2" class="w-4 h-4"></i>

                                        </button>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    {{-- ================================================= --}}
                    {{-- TOTALS --}}
                    {{-- ================================================= --}}

                    <div class="border-t border-gray-100 bg-gray-50/70 p-6">

                        <div class="max-w-md ml-auto space-y-4">

                            {{-- SOUS TOTAL --}}

                            <div class="flex items-center justify-between">

                                <span class="text-xs text-slate-500">
                                    Sous-total
                                </span>

                                <strong
                                    id="subtotal"
                                    class="text-sm text-slate-900"
                                >
                                    0 FCFA
                                </strong>

                            </div>


                            {{-- REMISE --}}

                            <div class="flex items-center justify-between gap-4">

                                <label
                                    for="remise"
                                    class="text-xs text-slate-500"
                                >
                                    Remise
                                </label>

                                <div class="flex items-center gap-2">

                                    <input
                                        type="number"
                                        id="remise"
                                        name="remise"
                                        value="{{ old('remise', 0) }}"
                                        min="0"
                                        step="0.01"
                                        class="w-32 px-3 py-2 rounded-xl border border-gray-200 text-xs text-right focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                    >

                                    <span class="text-xs text-slate-500">
                                        FCFA
                                    </span>

                                </div>

                            </div>


                            {{-- TVA --}}

                            <div class="flex items-center justify-between gap-4">

                                <label
                                    for="tva"
                                    class="text-xs text-slate-500"
                                >
                                    TVA
                                </label>

                                <div class="flex items-center gap-2">

                                    <input
                                        type="number"
                                        id="tva"
                                        name="tva"
                                        value="{{ old('tva', 18) }}"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="w-24 px-3 py-2 rounded-xl border border-gray-200 text-xs text-right focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                    >

                                    <span class="text-xs text-slate-500">
                                        %
                                    </span>

                                </div>

                            </div>


                            {{-- TOTAL TTC --}}

                            <div class="border-t border-gray-200 pt-4 flex items-center justify-between">

                                <span class="text-sm font-bold text-slate-900">
                                    Total TTC
                                </span>

                                <strong
                                    id="totalTtc"
                                    class="text-xl font-bold text-emerald-700"
                                >
                                    0 FCFA
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- CONDITIONS COMMERCIALES --}}
                {{-- ================================================= --}}

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6">

                    <div class="px-6 py-4 border-b border-gray-100">

                        <div class="flex items-center gap-3">

                            <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">

                                <i data-lucide="handshake" class="w-5 h-5"></i>

                            </div>

                            <div>

                                <h2 class="text-sm font-bold text-slate-900">
                                    Conditions commerciales
                                </h2>

                                <p class="text-xs text-slate-400">
                                    Précisez les conditions applicables au devis.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- DELAI --}}

                        <div>

                            <label
                                for="delaiRealisation"
                                class="block text-xs font-semibold text-slate-700 mb-2"
                            >
                                Délai de réalisation
                            </label>

                            <input
                                type="text"
                                id="delaiRealisation"
                                name="delai_realisation"
                                value="{{ old('delai_realisation') }}"
                                placeholder="Ex. 15 jours ouvrables"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                            >

                        </div>


                        {{-- CONDITIONS PAIEMENT --}}

                        <div>

                            <label
                                for="conditionsPaiement"
                                class="block text-xs font-semibold text-slate-700 mb-2"
                            >
                                Conditions de paiement
                            </label>

                            <input
                                type="text"
                                id="conditionsPaiement"
                                name="conditions_paiement"
                                value="{{ old('conditions_paiement') }}"
                                placeholder="Ex. 50% à la commande, 50% à la livraison"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                            >

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- ACTIONS --}}
                {{-- ================================================= --}}

                <div class="flex flex-col sm:flex-row justify-end gap-3 pb-8">

                    <a
                        href="{{ route('admin.devis') }}"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white border border-gray-200 text-slate-600 text-xs font-semibold hover:bg-gray-50 transition"
                    >

                        <i data-lucide="arrow-left" class="w-4 h-4"></i>

                        Annuler

                    </a>


                    <button
                        type="submit"
                        id="saveQuote"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed"
                    >

                        <i data-lucide="send" class="w-4 h-4"></i>

                        <span id="saveQuoteText">
                            Enregistrer et envoyer le devis
                        </span>

                    </button>

                </div>


            </form>

        </div>

    </main>

</div>


{{-- ===================================================== --}}
{{-- JAVASCRIPT --}}
{{-- ===================================================== --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | INITIALISATION LUCIDE
    |--------------------------------------------------------------------------
    */

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }


    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const quoteForm = document.getElementById('quoteForm');

    const linesBody = document.getElementById('linesBody');

    const addLineButton = document.getElementById('addLine');

    const subtotalElement =
        document.getElementById('subtotal');

    const remiseElement =
        document.getElementById('remise');

    const tvaElement =
        document.getElementById('tva');

    const totalTtcElement =
        document.getElementById('totalTtc');

    const saveQuoteButton =
        document.getElementById('saveQuote');

    const saveQuoteText =
        document.getElementById('saveQuoteText');


    let lineIndex = 1;


    /*
    |--------------------------------------------------------------------------
    | FORMAT MONNAIE
    |--------------------------------------------------------------------------
    */

    function formatMoney(value) {

        return new Intl.NumberFormat('fr-FR', {
            maximumFractionDigits: 0
        }).format(
            Math.round(value)
        ) + ' FCFA';

    }


    /*
    |--------------------------------------------------------------------------
    | CALCUL D'UNE LIGNE
    |--------------------------------------------------------------------------
    */

    function calculateLine(line) {

        const quantity =
            parseFloat(
                line.querySelector('.quantity')?.value
            ) || 0;


        const unitPrice =
            parseFloat(
                line.querySelector('.unit-price')?.value
            ) || 0;


        const total =
            quantity * unitPrice;


        const totalElement =
            line.querySelector('.line-total');


        if (totalElement) {

            totalElement.textContent =
                formatMoney(total);

        }


        return total;

    }


    /*
    |--------------------------------------------------------------------------
    | CALCUL DES TOTAUX
    |--------------------------------------------------------------------------
    */

    function calculateTotals() {

        let subtotal = 0;


        document
            .querySelectorAll('.quote-line')
            .forEach(function (line) {

                subtotal += calculateLine(line);

            });


        const remise =
            Math.max(
                0,
                parseFloat(remiseElement.value) || 0
            );


        const remiseEffective =
            Math.min(
                remise,
                subtotal
            );


        const base =
            Math.max(
                0,
                subtotal - remiseEffective
            );


        const tva =
            Math.max(
                0,
                parseFloat(tvaElement.value) || 0
            );


        const montantTva =
            base * (tva / 100);


        const totalTtc =
            base + montantTva;


        subtotalElement.textContent =
            formatMoney(subtotal);


        totalTtcElement.textContent =
            formatMoney(totalTtc);

    }


    /*
    |--------------------------------------------------------------------------
    | CREER UNE NOUVELLE LIGNE
    |--------------------------------------------------------------------------
    */

    addLineButton.addEventListener('click', function () {

        const row =
            document.createElement('tr');


        row.className =
            'quote-line border-b border-gray-100';


        row.innerHTML = `

            <td class="px-6 py-4">

                <input
                    type="text"
                    name="lignes[${lineIndex}][designation]"
                    placeholder="Ex. Développement du site web"
                    required
                    class="designation w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                >

            </td>


            <td class="px-4 py-4">

                <input
                    type="number"
                    name="lignes[${lineIndex}][quantite]"
                    value="1"
                    min="1"
                    step="1"
                    required
                    class="quantity w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs text-center focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                >

            </td>


            <td class="px-4 py-4">

                <input
                    type="number"
                    name="lignes[${lineIndex}][prix_unitaire]"
                    value="0"
                    min="0"
                    step="0.01"
                    required
                    class="unit-price w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs text-right focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                >

            </td>


            <td class="px-4 py-4 text-right">

                <span class="line-total text-sm font-bold text-slate-900">
                    0 FCFA
                </span>

            </td>


            <td class="px-4 py-4 text-center">

                <button
                    type="button"
                    class="remove-line h-8 w-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition"
                    title="Supprimer la ligne"
                >

                    <i data-lucide="trash-2" class="w-4 h-4"></i>

                </button>

            </td>

        `;


        linesBody.appendChild(row);


        lineIndex++;


        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }


        calculateTotals();

    });


    /*
    |--------------------------------------------------------------------------
    | SUPPRIMER UNE LIGNE
    |--------------------------------------------------------------------------
    */

    linesBody.addEventListener('click', function (event) {

        const button =
            event.target.closest('.remove-line');


        if (!button) {
            return;
        }


        const lines =
            document.querySelectorAll('.quote-line');


        if (lines.length <= 1) {

            const line =
                button.closest('.quote-line');


            line.querySelector('.designation').value = '';

            line.querySelector('.quantity').value = 1;

            line.querySelector('.unit-price').value = 0;

        } else {

            button
                .closest('.quote-line')
                .remove();

        }


        calculateTotals();

    });


    /*
    |--------------------------------------------------------------------------
    | RECALCUL AUTOMATIQUE DES LIGNES
    |--------------------------------------------------------------------------
    */

    linesBody.addEventListener('input', function () {

        calculateTotals();

    });


    /*
    |--------------------------------------------------------------------------
    | RECALCUL REMISE
    |--------------------------------------------------------------------------
    */

    remiseElement.addEventListener('input', function () {

        calculateTotals();

    });


    /*
    |--------------------------------------------------------------------------
    | RECALCUL TVA
    |--------------------------------------------------------------------------
    */

    tvaElement.addEventListener('input', function () {

        calculateTotals();

    });


    /*
    |--------------------------------------------------------------------------
    | VALIDATION AVANT ENVOI
    |--------------------------------------------------------------------------
    */

    quoteForm.addEventListener('submit', function (event) {

        const lines =
            document.querySelectorAll('.quote-line');


        let hasValidLine = false;


        lines.forEach(function (line) {

            const designation =
                line.querySelector('.designation')
                    ?.value
                    .trim();


            const quantity =
                parseFloat(
                    line.querySelector('.quantity')?.value
                ) || 0;


            const unitPrice =
                parseFloat(
                    line.querySelector('.unit-price')?.value
                );


            if (
                designation !== '' &&
                quantity > 0 &&
                !isNaN(unitPrice) &&
                unitPrice >= 0
            ) {

                hasValidLine = true;

            }

        });


        if (!hasValidLine) {

            event.preventDefault();


            alert(
                'Veuillez ajouter au moins une prestation ou un produit valide au devis.'
            );


            return;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPECHER LES DOUBLES CLICS
        |--------------------------------------------------------------------------
        */

        saveQuoteButton.disabled = true;

        saveQuoteText.textContent =
            'Enregistrement et envoi...';

    });


    /*
    |--------------------------------------------------------------------------
    | INITIALISATION
    |--------------------------------------------------------------------------
    */

    calculateTotals();

});

</script>

</body>

</html>