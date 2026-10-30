<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Planetech Administration - Décharges</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            50: '#f2f7f4',
                            700: '#1b4d3e',
                            900: '#0f2c23'
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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

    <style>
        [x-cloak],
        [hidden] {
            display: none !important;
        }

        body {
            margin: 0;
        }

        input,
        select,
        textarea,
        button {
            font: inherit;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #1b4d3e;
            box-shadow: 0 0 0 3px rgba(27, 77, 62, 0.09);
        }

        input:disabled,
        select:disabled,
        textarea:disabled {
            cursor: not-allowed;
        }

        #signature-pad {
            display: block;
            touch-action: none;
        }

        .materiel-row {
            min-width: 0;
        }

        .historique-card {
            overflow-wrap: anywhere;
        }

        @media (max-width: 640px) {
            .decharges-main {
                padding: 1rem !important;
            }

            .decharge-card {
                padding: 1rem !important;
            }

            .type-grid {
                grid-template-columns: 1fr !important;
            }

            .type-grid label {
                justify-content: flex-start;
            }

            .materiel-row {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            .materiel-row > div {
                grid-column: span 1 / span 1 !important;
            }

            .historique-actions {
                flex-wrap: wrap;
            }

            .historique-actions a {
                min-width: 0;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">

    @include('admin.aside')

    <div class="flex-1 flex flex-col min-w-0 min-h-0 overflow-y-auto">

        <main class="decharges-main w-full max-w-full p-6 lg:p-8 space-y-6">

            {{-- MESSAGES DE RETOUR --}}

            @if(session('success'))
                <div class="px-4 py-3 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 font-medium text-sm flex items-start gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="px-4 py-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-800 font-medium text-sm flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="px-4 py-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-800 text-sm">
                    <p class="font-semibold mb-2">
                        Veuillez corriger les erreurs suivantes :
                    </p>

                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- EN-TÊTE DE PAGE --}}

            <div class="flex items-center gap-3 min-w-0">

                <div class="h-12 w-12 shrink-0 rounded-2xl bg-forest-700 text-white flex items-center justify-center shadow-lg shadow-forest-700/20">
                    <i data-lucide="file-signature" class="w-6 h-6"></i>
                </div>

                <div class="min-w-0">
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900">
                        Gestion des Décharges
                    </h1>

                    <p class="text-sm text-slate-muted mt-1 leading-relaxed">
                        Établissez des fiches de décharge multi-matériels signées numériquement.
                    </p>
                </div>

            </div>

            {{-- CONTENU PRINCIPAL --}}

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 xl:gap-8 items-start">

                {{-- FORMULAIRE DE CRÉATION --}}

                <section class="lg:col-span-7 min-w-0 bg-white rounded-2xl border border-gray-100 p-5 lg:p-6 shadow-sm decharge-card">

                    <div class="flex items-center gap-2 border-b border-gray-100 pb-4 mb-5">
                        <i data-lucide="pen-tool" class="w-5 h-5 text-forest-700 shrink-0"></i>

                        <h2 class="text-lg font-semibold text-slate-900">
                            Nouvelle Décharge
                        </h2>
                    </div>

                    <form
                        action="{{ route('admin.decharges.store') }}"
                        method="POST"
                        id="decharge-form"
                        class="space-y-5"
                    >
                        @csrf

                        {{-- TYPE DE DÉCHARGE --}}

                        @php
                            $oldType = old('type', 'argent');
                        @endphp

                        <div class="space-y-2">

                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Type de Décharge
                            </label>

                            <div class="type-grid grid grid-cols-1 sm:grid-cols-3 gap-3">

                                <label class="flex items-center justify-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-emerald-50/50 has-[:checked]:border-forest-700 has-[:checked]:bg-emerald-50 transition-all">

                                    <input
                                        type="radio"
                                        name="type"
                                        value="argent"
                                        class="sr-only"
                                        {{ $oldType === 'argent' ? 'checked' : '' }}
                                        onchange="toggleTypeFields('argent')"
                                    >

                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2">
                                        <i data-lucide="banknote" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                        Argent
                                    </span>

                                </label>

                                <label class="flex items-center justify-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-emerald-50/50 has-[:checked]:border-forest-700 has-[:checked]:bg-emerald-50 transition-all">

                                    <input
                                        type="radio"
                                        name="type"
                                        value="materiel"
                                        class="sr-only"
                                        {{ $oldType === 'materiel' ? 'checked' : '' }}
                                        onchange="toggleTypeFields('materiel')"
                                    >

                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2">
                                        <i data-lucide="box" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                        Matériel / Doc
                                    </span>

                                </label>

                                <label class="flex items-center justify-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-emerald-50/50 has-[:checked]:border-forest-700 has-[:checked]:bg-emerald-50 transition-all">

                                    <input
                                        type="radio"
                                        name="type"
                                        value="responsabilite"
                                        class="sr-only"
                                        {{ $oldType === 'responsabilite' ? 'checked' : '' }}
                                        onchange="toggleTypeFields('responsabilite')"
                                    >

                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                        Responsabilité
                                    </span>

                                </label>

                            </div>

                        </div>

                        {{-- IDENTITÉ DU BÉNÉFICIAIRE --}}

                        <div class="border-t border-gray-100 pt-5">

                            <h3 class="text-sm font-semibold text-slate-800 mb-4">
                                Informations du bénéficiaire
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4">

                                <div class="min-w-0">
                                    <label for="nom" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Nom & Prénom *
                                    </label>

                                    <input
                                        type="text"
                                        id="nom"
                                        name="nom"
                                        value="{{ old('nom') }}"
                                        required
                                        maxlength="255"
                                        autocomplete="name"
                                        class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                    >
                                </div>

                                <div class="min-w-0">
                                    <label for="role" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Rôle *
                                    </label>

                                    <input
                                        type="text"
                                        id="role"
                                        name="role"
                                        value="{{ old('role') }}"
                                        placeholder="Ex. : Employé, Prestataire"
                                        required
                                        maxlength="255"
                                        class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                    >
                                </div>

                                <div class="min-w-0">
                                    <label for="entreprise" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Entreprise
                                    </label>

                                    <input
                                        type="text"
                                        id="entreprise"
                                        name="entreprise"
                                        value="{{ old('entreprise') }}"
                                        maxlength="255"
                                        class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                    >
                                </div>

                                <div class="min-w-0">
                                    <label for="numero" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Téléphone *
                                    </label>

                                    <input
                                        type="text"
                                        id="numero"
                                        name="numero"
                                        value="{{ old('numero') }}"
                                        required
                                        maxlength="50"
                                        autocomplete="tel"
                                        class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                    >
                                </div>

                                <div class="md:col-span-2 min-w-0">
                                    <label for="email" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Adresse E-mail *
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        maxlength="255"
                                        autocomplete="email"
                                        class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                    >
                                </div>

                            </div>

                        </div>

                        {{-- SECTION ARGENT --}}

                        <div id="fields-argent" class="space-y-4 pt-5 border-t border-gray-100">

                            <h3 class="text-sm font-semibold text-slate-800">
                                Détails de la décharge financière
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                <div class="min-w-0">
                                    <label for="somme" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Somme (FCFA) *
                                    </label>

                                    <input
                                        type="number"
                                        id="somme"
                                        name="somme"
                                        value="{{ old('somme') }}"
                                        min="0"
                                        step="any"
                                        required
                                        class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                    >
                                </div>

                                <div class="min-w-0">
                                    <label for="motif" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Motif *
                                    </label>

                                    <input
                                        type="text"
                                        id="motif"
                                        name="motif"
                                        value="{{ old('motif') }}"
                                        placeholder="Ex. : Achat d'outillage"
                                        required
                                        maxlength="255"
                                        class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                    >
                                </div>

                            </div>

                        </div>

                        {{-- SECTION MATÉRIEL ET DOCUMENTS --}}

                        <div id="fields-materiel" class="space-y-5 pt-5 border-t border-gray-100" hidden>

                            {{-- FOURNISSEUR / LIVREUR --}}

                            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 space-y-4">

                                <div class="flex items-start gap-3">

                                    <div class="rounded-lg bg-emerald-100 p-2 text-emerald-800 shrink-0">
                                        <i data-lucide="truck" class="w-5 h-5"></i>
                                    </div>

                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-slate-900">
                                            Fournisseur / Livreur
                                        </h3>

                                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                            Identité de la personne ou de l'entreprise qui livre le matériel à PLANETECH SARL.
                                        </p>
                                    </div>

                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4">

                                    <div class="min-w-0">
                                        <label for="fournisseur_nom" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Nom du livreur *
                                        </label>

                                        <input
                                            type="text"
                                            id="fournisseur_nom"
                                            name="fournisseur_nom"
                                            value="{{ old('fournisseur_nom') }}"
                                            required
                                            maxlength="255"
                                            placeholder="Nom et prénom du livreur"
                                            class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                        >
                                    </div>

                                    <div class="min-w-0">
                                        <label for="fournisseur_entreprise" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Entreprise du fournisseur
                                        </label>

                                        <input
                                            type="text"
                                            id="fournisseur_entreprise"
                                            name="fournisseur_entreprise"
                                            value="{{ old('fournisseur_entreprise') }}"
                                            maxlength="255"
                                            placeholder="Nom de l'entreprise"
                                            class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                        >
                                    </div>

                                    <div class="min-w-0">
                                        <label for="fournisseur_numero" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Téléphone du livreur *
                                        </label>

                                        <input
                                            type="text"
                                            id="fournisseur_numero"
                                            name="fournisseur_numero"
                                            value="{{ old('fournisseur_numero') }}"
                                            required
                                            maxlength="50"
                                            placeholder="Numéro de téléphone"
                                            class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                        >
                                    </div>

                                    <div class="min-w-0">
                                        <label for="fournisseur_email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            E-mail du livreur *
                                        </label>

                                        <input
                                            type="email"
                                            id="fournisseur_email"
                                            name="fournisseur_email"
                                            value="{{ old('fournisseur_email') }}"
                                            required
                                            maxlength="255"
                                            placeholder="livreur@entreprise.com"
                                            class="w-full min-w-0 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm"
                                        >
                                    </div>

                                </div>

                            </div>

                            {{-- LISTE DES MATÉRIELS --}}

                            <div class="space-y-3">

                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                                    <div>
                                        <h3 class="text-sm font-bold text-slate-800">
                                            Liste des matériels / documents
                                        </h3>

                                        <p class="text-xs text-slate-500 mt-1">
                                            Ajoutez les articles remis ou réceptionnés.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        id="add-materiel-btn"
                                        class="shrink-0 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-semibold rounded-lg transition-all"
                                    >
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                        Ajouter un matériel
                                    </button>

                                </div>

                                <div id="materiels-container" class="space-y-3">

                                    {{-- PREMIÈRE LIGNE --}}

                                    <div class="materiel-row p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">

                                        <div class="min-w-0 lg:col-span-4">
                                            <label for="materiel-designation-0" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                                Désignation *
                                            </label>

                                            <input
                                                type="text"
                                                id="materiel-designation-0"
                                                name="materiels[0][designation]"
                                                value="{{ old('materiels.0.designation') }}"
                                                placeholder="Ex. : Routeur, ordinateur"
                                                required
                                                maxlength="255"
                                                class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm"
                                            >
                                        </div>

                                        <!-- Quantité -->
<div class="min-w-0 lg:col-span-1">
    <label for="materiel-quantite-0" class="block text-xs font-semibold text-slate-600 mb-1.5">
        Quantité
    </label>

    <input
        type="number"
        id="materiel-quantite-0"
        name="materiels[0][quantite]"
        value="{{ old('materiels.0.quantite', 1) }}"
        min="1"
        step="1"
        placeholder="Qté"
        class="w-full min-w-[90px] px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm"
    >
</div>

<!-- Prix unitaire avec un espace ajouté -->
<div class="min-w-0 lg:col-span-2 lg:pl-3">
    <label for="materiel-prix-unitaire-0" class="block text-xs font-semibold text-slate-600 mb-1.5">
        Prix unitaire
    </label>

    <input
        type="number"
        id="materiel-prix-unitaire-0"
        name="materiels[0][prix_unitaire]"
        value="{{ old('materiels.0.prix_unitaire') }}"
        min="0"
        step="0.01"
        placeholder="FCFA"
        class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm prix-unitaire"
    >
</div>

                                        <div class="min-w-0 lg:col-span-2">
                                            <label for="materiel-prix-global-0" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                                Prix global
                                            </label>

                                            <input
                                                type="number"
                                                id="materiel-prix-global-0"
                                                name="materiels[0][prix_global]"
                                                value="{{ old('materiels.0.prix_global') }}"
                                                min="0"
                                                step="0.01"
                                                placeholder="FCFA"
                                                class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm prix-global"
                                            >
                                        </div>

                                        <div class="min-w-0 lg:col-span-2">
                                            <label for="materiel-etat-0" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                                État *
                                            </label>

                                            <select
                                                id="materiel-etat-0"
                                                name="materiels[0][etat]"
                                                required
                                                class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm"
                                            >
                                                <option value="neuf" {{ old('materiels.0.etat', 'neuf') === 'neuf' ? 'selected' : '' }}>
                                                    Neuf
                                                </option>

                                                <option value="partiellement_utilise" {{ old('materiels.0.etat') === 'partiellement_utilise' ? 'selected' : '' }}>
                                                    Partiellement utilisé
                                                </option>

                                                <option value="vieux" {{ old('materiels.0.etat') === 'vieux' ? 'selected' : '' }}>
                                                    Vieux
                                                </option>

                                                <option value="defectueux" {{ old('materiels.0.etat') === 'defectueux' ? 'selected' : '' }}>
                                                    Défectueux
                                                </option>
                                            </select>
                                        </div>

                                        <div class="hidden lg:block lg:col-span-1"></div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        {{-- SECTION RESPONSABILITÉ --}}

                        <div id="fields-responsabilite" class="space-y-4 pt-5 border-t border-gray-100" hidden>

                            <div>
                                <label for="description" class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Description / Engagement *
                                </label>

                                <textarea
                                    id="description"
                                    name="description"
                                    rows="4"
                                    required
                                    maxlength="10000"
                                    placeholder="Saisir les conditions précises..."
                                    class="w-full px-3.5 py-3 rounded-xl border border-gray-200 bg-white text-sm resize-y leading-relaxed"
                                >{{ old('description') }}</textarea>
                            </div>

                        </div>

                        {{-- SIGNATURE NUMÉRIQUE --}}

                        <div class="pt-5 border-t border-gray-100 space-y-2">

                            <div class="flex justify-between items-center gap-3">

                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                    Signature numérique *
                                </label>

                                <button
                                    type="button"
                                    id="clear-signature"
                                    class="shrink-0 text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline"
                                >
                                    Effacer
                                </button>

                            </div>

                            <p class="text-xs text-slate-500">
                                Signez dans la zone ci-dessous avec votre souris ou votre doigt.
                            </p>

                            <div class="border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 relative h-36 sm:h-40 overflow-hidden">

                                <canvas
                                    id="signature-pad"
                                    class="w-full h-full rounded-xl cursor-crosshair"
                                    aria-label="Zone de signature numérique"
                                ></canvas>

                            </div>

                            <input
                                type="hidden"
                                name="signature_data"
                                id="signature-input"
                            >

                        </div>

                        {{-- BOUTON DE VALIDATION --}}

                        <div class="pt-1">

                            <button
                                type="submit"
                                id="submit-btn"
                                class="w-full min-h-12 px-4 py-3 bg-forest-700 hover:bg-forest-900 disabled:cursor-not-allowed disabled:opacity-60 text-white text-sm font-semibold rounded-xl shadow-md transition-all flex items-center justify-center gap-2"
                            >
                                <i data-lucide="send" class="w-4 h-4 shrink-0"></i>

                                <span>Valider et envoyer par e-mail</span>
                            </button>

                        </div>

                    </form>

                </section>

                {{-- HISTORIQUE DES DÉCHARGES --}}

                <aside class="lg:col-span-5 min-w-0 bg-white rounded-2xl border border-gray-100 p-5 lg:p-6 shadow-sm space-y-4 decharge-card">

                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-4">

                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">
                                Historique récent
                            </h2>

                            <p class="text-xs text-slate-500 mt-1">
                                Consultez les décharges enregistrées.
                            </p>
                        </div>

                        <div class="w-10 h-10 shrink-0 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <i data-lucide="history" class="w-5 h-5 text-forest-700"></i>
                        </div>

                    </div>

                    <div class="space-y-3">

                        @forelse($decharges as $d)

                            <article class="historique-card p-4 rounded-xl border border-slate-100 bg-slate-50/70 space-y-3">

                                {{-- RÉFÉRENCE ET DATE --}}

                                <div class="flex flex-wrap justify-between items-center gap-2">

                                    <span class="font-bold text-xs text-forest-700 bg-emerald-100 px-2.5 py-1 rounded-md">
                                        {{ $d->reference }}
                                    </span>

                                    <span class="text-xs text-slate-400">
                                        {{ $d->created_at->format('d/m/Y') }}
                                    </span>

                                </div>

                                {{-- BÉNÉFICIAIRE --}}

                                <div class="space-y-1">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $d->nom }}
                                    </p>

                                    <p class="text-xs text-slate-500">
                                        Type :
                                        <span class="font-medium capitalize">
                                            @switch($d->type)
                                                @case('argent')
                                                    Argent
                                                    @break

                                                @case('materiel')
                                                    Matériel / Document
                                                    @break

                                                @case('responsabilite')
                                                    Responsabilité
                                                    @break

                                                @default
                                                    {{ $d->type }}
                                            @endswitch
                                        </span>
                                    </p>

                                </div>

                                {{-- DÉTAILS DES MATÉRIELS --}}

                                @if($d->type === 'materiel' && $d->items && $d->items->count() > 0)

                                    <div class="text-xs bg-white p-3 rounded-lg border border-slate-200 text-slate-600 space-y-2">

                                        @foreach($d->items as $item)

                                            <div class="flex items-start gap-2">

                                                <span class="text-emerald-600 font-bold">•</span>

                                                <span class="min-w-0">
                                                    {{ $item->designation }}
                                                    <span class="text-slate-400">
                                                        (Qté : {{ $item->quantite }})
                                                    </span>
                                                </span>

                                            </div>

                                        @endforeach

                                    </div>

                                @endif

                                {{-- ACTIONS PDF --}}

                                <div class="historique-actions flex items-center gap-2 pt-1">

                                    <a
                                        href="{{ route('admin.decharges.pdf', $d) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex-1 min-w-0 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-forest-700 hover:bg-forest-900 text-white text-xs font-semibold rounded-lg transition-all"
                                    >
                                        <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                                        <span>Voir le PDF</span>
                                    </a>

                                    <a
                                        href="{{ route('admin.decharges.pdf', ['decharge' => $d, 'download' => 1]) }}"
                                        class="shrink-0 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-semibold rounded-lg transition-all"
                                        title="Télécharger le PDF"
                                        aria-label="Télécharger le PDF de la décharge {{ $d->reference }}"
                                    >
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </a>

                                </div>

                            </article>

                        @empty

                            <div class="flex flex-col items-center justify-center text-center py-10 px-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">

                                <div class="w-12 h-12 rounded-full bg-white border border-slate-100 flex items-center justify-center mb-3">
                                    <i data-lucide="file-text" class="w-6 h-6 text-slate-400"></i>
                                </div>

                                <p class="text-sm font-medium text-slate-600">
                                    Aucune décharge enregistrée
                                </p>

                                <p class="text-xs text-slate-400 mt-1">
                                    Les nouvelles décharges apparaîtront ici.
                                </p>

                            </div>

                        @endforelse

                    </div>

                    {{-- PAGINATION --}}

                    @if($decharges->hasPages())
                        <div class="pt-3 border-t border-gray-100 overflow-x-auto">
                            {{ $decharges->links() }}
                        </div>
                    @endif

                </aside>

            </div>

        </main>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // INITIALISATION DES ICÔNES

    if (window.lucide) {
        lucide.createIcons();
    }

    // 1. GESTION DES TYPES DE DÉCHARGE

    const form = document.getElementById('decharge-form');

    const typeInputs = document.querySelectorAll('input[name="type"]');

    function toggleTypeFields(selectedType) {

        ['argent', 'materiel', 'responsabilite'].forEach(function (type) {

            const section = document.getElementById('fields-' + type);

            if (!section) {
                return;
            }

            const active = type === selectedType;

            section.hidden = !active;

            section.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !active;
            });

        });

        // Mettre à jour l'affichage des boutons de type.

        if (window.lucide) {
            lucide.createIcons();
        }
    }

    const selectedTypeInput = document.querySelector('input[name="type"]:checked');

    toggleTypeFields(selectedTypeInput ? selectedTypeInput.value : 'argent');

    typeInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            toggleTypeFields(this.value);
        });
    });

    // 2. GESTION DES LIGNES DE MATÉRIEL

    const addMaterielButton = document.getElementById('add-materiel-btn');

    const materielsContainer = document.getElementById('materiels-container');

    let materielIndex = 1;

    addMaterielButton.addEventListener('click', function () {

        const index = materielIndex++;

        const row = document.createElement('div');

        row.className = 'materiel-row p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end';

        row.innerHTML = `
            <div class="min-w-0 lg:col-span-4">
                <label for="materiel-designation-${index}" class="block text-xs font-semibold text-slate-600 mb-1.5">
                    Désignation *
                </label>

                <input
                    type="text"
                    id="materiel-designation-${index}"
                    name="materiels[${index}][designation]"
                    placeholder="Ex. : Caméra de surveillance"
                    maxlength="255"
                    class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm"
                    required
                >
            </div>

            <div class="min-w-0 lg:col-span-1">
                <label for="materiel-quantite-${index}" class="block text-xs font-semibold text-slate-600 mb-1.5">
                    Quantité
                </label>

                <input
                    type="number"
                    id="materiel-quantite-${index}"
                    name="materiels[${index}][quantite]"
                    value="1"
                    min="1"
                    step="1"
                    placeholder="Qté"
                    class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm"
                >
            </div>

            <div class="min-w-0 lg:col-span-2">
                <label for="materiel-prix-unitaire-${index}" class="block text-xs font-semibold text-slate-600 mb-1.5">
                    Prix unitaire
                </label>

                <input
                    type="number"
                    id="materiel-prix-unitaire-${index}"
                    name="materiels[${index}][prix_unitaire]"
                    min="0"
                    step="0.01"
                    placeholder="FCFA"
                    class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm prix-unitaire"
                >
            </div>

            <div class="min-w-0 lg:col-span-2">
                <label for="materiel-prix-global-${index}" class="block text-xs font-semibold text-slate-600 mb-1.5">
                    Prix global
                </label>

                <input
                    type="number"
                    id="materiel-prix-global-${index}"
                    name="materiels[${index}][prix_global]"
                    min="0"
                    step="0.01"
                    placeholder="FCFA"
                    class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm prix-global"
                >
            </div>

            <div class="min-w-0 lg:col-span-2">
                <label for="materiel-etat-${index}" class="block text-xs font-semibold text-slate-600 mb-1.5">
                    État *
                </label>

                <select
                    id="materiel-etat-${index}"
                    name="materiels[${index}][etat]"
                    required
                    class="w-full min-w-0 px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm"
                >
                    <option value="neuf">Neuf</option>
                    <option value="partiellement_utilise">Partiellement utilisé</option>
                    <option value="vieux">Vieux</option>
                    <option value="defectueux">Défectueux</option>
                </select>
            </div>

            <div class="flex justify-end lg:col-span-1">
                <button
                    type="button"
                    class="remove-materiel inline-flex items-center justify-center p-2.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg transition-all"
                    title="Supprimer ce matériel"
                    aria-label="Supprimer ce matériel"
                >
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        `;

        materielsContainer.appendChild(row);

        if (window.lucide) {
            lucide.createIcons({
                nodes: [row]
            });
        }

        const designation = row.querySelector('input[name$="[designation]"]');

        if (designation) {
            designation.focus();
        }
    });

    // SUPPRESSION D'UNE LIGNE

    materielsContainer.addEventListener('click', function (event) {

        const button = event.target.closest('.remove-materiel');

        if (!button) {
            return;
        }

        const row = button.closest('.materiel-row');

        if (row) {
            row.remove();
        }
    });

    // 3. SIGNATURE NUMÉRIQUE

    const canvas = document.getElementById('signature-pad');

    const signatureInput = document.getElementById('signature-input');

    const clearSignatureButton = document.getElementById('clear-signature');

    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(248, 250, 252)',
        penColor: '#0f2c23',
        minWidth: 0.8,
        maxWidth: 2.5
    });

    let signatureHasBeenDrawn = false;

    function resizeCanvas() {

        const ratio = Math.max(window.devicePixelRatio || 1, 1);

        const rect = canvas.getBoundingClientRect();

        if (!rect.width || !rect.height) {
            return;
        }

        // Sauvegarder le tracé avant de redimensionner le canvas.

        const previousSignature = signaturePad.isEmpty()
            ? null
            : signaturePad.toData();

        canvas.width = Math.round(rect.width * ratio);

        canvas.height = Math.round(rect.height * ratio);

        const context = canvas.getContext('2d');

        context.setTransform(ratio, 0, 0, ratio, 0, 0);

        signaturePad.clear();

        if (previousSignature && previousSignature.length > 0) {

            signaturePad.fromData(previousSignature);

            signatureHasBeenDrawn = true;

        } else {

            signatureHasBeenDrawn = false;
        }
    }

    // Attendre que le navigateur calcule la taille réelle du canvas.

    requestAnimationFrame(resizeCanvas);

    let resizeTimeout;

    window.addEventListener('resize', function () {

        clearTimeout(resizeTimeout);

        resizeTimeout = setTimeout(resizeCanvas, 150);
    });

    signaturePad.addEventListener('beginStroke', function () {
        signatureHasBeenDrawn = true;
    });

    clearSignatureButton.addEventListener('click', function () {

        signaturePad.clear();

        signatureHasBeenDrawn = false;

        signatureInput.value = '';
    });

    // 4. VALIDATION ET ENVOI DU FORMULAIRE

    form.addEventListener('submit', function (event) {

        if (signaturePad.isEmpty() || !signatureHasBeenDrawn) {

            event.preventDefault();

            alert('Veuillez apposer votre signature avant de valider la décharge.');

            return;
        }

        signatureInput.value = signaturePad.toDataURL('image/png');

        // Empêcher les doubles soumissions.

        const submitButton = document.getElementById('submit-btn');

        submitButton.disabled = true;

        submitButton.classList.add('opacity-60');

        const buttonText = submitButton.querySelector('span');

        if (buttonText) {
            buttonText.textContent = 'Envoi en cours...';
        }
    });

});
</script>

</body>
</html>
