<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Planetech Administration - Boutique</title>

    {{-- Tailwind --}}
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
        }
    </script>

    {{-- Lucide --}}
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-gray-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- ========================================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================================= --}}

    @include('admin.aside')


    {{-- ========================================================= --}}
    {{-- CONTENU PRINCIPAL --}}
    {{-- ========================================================= --}}

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        <main class="p-8 space-y-6">

            {{-- ========================================================= --}}
            {{-- MESSAGE SUCCÈS --}}
            {{-- ========================================================= --}}

            @if(session('success'))

                <div
                    class="px-4 py-3 rounded-xl bg-green-100 border border-green-300
                           text-green-700 font-medium text-sm"
                >
                    {{ session('success') }}
                </div>

            @endif


            {{-- ========================================================= --}}
            {{-- MESSAGE ERREUR --}}
            {{-- ========================================================= --}}

            @if(session('error'))

                <div
                    class="px-4 py-3 rounded-xl bg-red-100 border border-red-300
                           text-red-700 font-medium text-sm"
                >
                    {{ session('error') }}
                </div>

            @endif


            {{-- ========================================================= --}}
            {{-- ERREURS VALIDATION --}}
            {{-- ========================================================= --}}

            @if($errors->any())

                <div
                    class="px-4 py-3 rounded-xl bg-red-50 border border-red-200
                           text-red-700 text-sm"
                 >

                    <ul class="list-disc list-inside space-y-1">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- ========================================================= --}}
            {{-- BOUTON BOUTIQUE --}}
            {{-- ========================================================= --}}

            <div class="flex items-center gap-3">

                        <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="store" class="w-5 h-5"></i>
                        </div>

                       <div>

                            <h1 class="text-2xl font-bold text-slate-900">
                                Boutique
                            </h1>

                            <p class="text-sm text-slate-muted mt-1">
                                Consultez les produits de la Boutique.
                            </p>

                        </div>
            </div>


            {{-- ========================================================= --}}
            {{-- GRILLE PRINCIPALE --}}
            {{-- ========================================================= --}}

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">


                {{-- ===================================================== --}}
                {{-- FORMULAIRE PRODUIT --}}
                {{-- ===================================================== --}}

                <div
                    class="lg:col-span-7 bg-white rounded-2xl border border-gray-100
                           p-6 shadow-sm"
                >

                    {{-- TITRE --}}

                    <div class="mb-6">

                        <h3 class="font-bold text-slate-900 text-base">

                            {{ isset($product)
                                ? 'Modifier le produit'
                                : 'Ajouter un produit'
                            }}

                        </h3>

                        <p class="text-xs text-slate-muted mt-1">

                            {{ isset($product)
                                ? 'Modifiez les informations du produit.'
                                : 'Le produit apparaîtra dans le catalogue public.'
                            }}

                        </p>

                    </div>


                    {{-- ================================================= --}}
                    {{-- FORMULAIRE --}}
                    {{-- ================================================= --}}

                    <form
                        action="{{ isset($product)
                            ? route('produits.update', $product->id)
                            : route('produits.store')
                        }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-5"
                     >

                        @csrf

                        @if(isset($product))
                            @method('PUT')
                        @endif


                        {{-- ================================================= --}}
                        {{-- NOM DU PRODUIT --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="name"
                                class="block text-xs font-semibold text-slate-700 mb-1.5"
                            >

                                Nom du produit

                                <span class="text-red-500">*</span>

                            </label>


                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $product->name ?? '') }}"
                                placeholder="Ex. Switch 24 ports Gigabit"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
                                       text-sm text-slate-700 focus:outline-none
                                       focus:border-forest-700 bg-white"
                                required
                            >


                            @error('name')

                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- ================================================= --}}
                        {{-- SOUS-CATEGORIE + PRIX --}}
                        {{-- ================================================= --}}

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">


                            {{-- SOUS-CATEGORIE --}}

                            <div>

                                <label
                                    for="sous_categorie_id"
                                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                                >

                                    Sous-catégorie

                                    <span class="text-red-500">*</span>

                                </label>


                                <div class="relative">

                                    <select
                                        id="sous_categorie_id"
                                        name="sous_categorie_id"
                                        class="w-full px-3.5 py-2.5 rounded-xl border
                                               border-gray-200 text-sm text-slate-700
                                               focus:outline-none
                                               focus:border-forest-700 bg-white
                                               appearance-none pr-10"
                                        required
                                    >

                                        <option value="">
                                            Sélectionner une sous-catégorie
                                        </option>


                                        @foreach($sousCategories as $sousCategorie)

                                            <option
                                                value="{{ $sousCategorie->id }}"
                                                {{ old(
                                                    'sous_categorie_id',
                                                    $product->sous_categorie_id ?? ''
                                                ) == $sousCategorie->id
                                                    ? 'selected'
                                                    : ''
                                                }}
                                            >

                                                {{ $sousCategorie->category->nom ?? 'Sans catégorie' }}
                                                —
                                                {{ $sousCategorie->nom }}

                                            </option>

                                        @endforeach

                                    </select>


                                    <i
                                        data-lucide="chevron-down"
                                        class="w-4 h-4 text-slate-400 absolute
                                               right-3.5 top-1/2
                                               -translate-y-1/2
                                               pointer-events-none"
                                    ></i>

                                </div>


                                @error('sous_categorie_id')

                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- PRIX --}}

                            <div>

                                <label
                                    for="price"
                                    class="block text-xs font-semibold
                                           text-slate-700 mb-1.5"
                                >
                                    Prix (FCFA)
                                </label>


                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    value="{{ old('price', $product->price ?? '') }}"
                                    placeholder="185000"
                                    min="0"
                                    step="1"
                                    class="w-full px-3.5 py-2.5 rounded-xl
                                           border border-gray-200 text-sm
                                           text-slate-700 focus:outline-none
                                           focus:border-forest-700 bg-white"
                                >


                                <p class="text-[11px] text-slate-muted mt-1">
                                    Laissez vide pour afficher « Sur devis ».
                                </p>


                                @error('price')

                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- DESCRIPTION --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="description"
                                class="block text-xs font-semibold
                                       text-slate-700 mb-1.5"
                            >

                                Description

                                <span class="text-red-500">*</span>

                            </label>


                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Caractéristiques principales, usage recommandé, garantie..."
                                class="w-full px-3.5 py-2.5 rounded-xl border
                                       border-gray-200 text-sm text-slate-700
                                       focus:outline-none focus:border-forest-700
                                       bg-white resize-none"
                                
                            >{{ old('description', $product->description ?? '') }}</textarea>


                            @error('description')

                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- ================================================= --}}
                        {{-- FICHE TECHNIQUE PDF (FACULTATIVE) --}}
                        {{-- ================================================= --}}

                        <div>
                            <label
                                for="fiche_technique"
                                class="block text-xs font-semibold text-slate-700 mb-1.5"
                            >
                                Fiche technique PDF
                                <span class="text-slate-400 font-normal">(Facultatif)</span>
                            </label>

                            <div class="border border-gray-200 rounded-xl p-4 bg-white">
                                @if(isset($product) && $product->fiche_technique)
                                    <div class="flex items-center gap-2 mb-3 text-sm text-emerald-700">
                                        <i data-lucide="file-pdf" class="w-5 h-5"></i>
                                        <a
                                            href="{{ asset('storage/' . $product->fiche_technique) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="underline font-medium"
                                        >
                                            Consulter la fiche technique actuelle
                                        </a>
                                    </div>
                                @endif

                                <div class="flex flex-wrap items-center gap-3">
                                    <label
                                        for="fiche_technique"
                                        class="cursor-pointer px-3 py-2 rounded-lg border border-gray-200 text-xs font-medium text-slate-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Choisir un PDF
                                    </label>

                                    <span id="fiche-technique-name" class="text-xs text-slate-muted">
                                        Aucun fichier choisi
                                    </span>

                                    <input
                                        type="file"
                                        id="fiche_technique"
                                        name="fiche_technique"
                                        accept=".pdf,application/pdf"
                                        class="hidden"
                                    >
                                </div>

                                <p class="text-[11px] text-slate-muted mt-2">
                                    Format PDF uniquement. Facultatif.
                                </p>

                                @error('fiche_technique')
                                    <p class="text-xs text-red-500 mt-1">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>


                        {{-- ================================================= --}}
                        {{-- PHOTO --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                class="block text-xs font-semibold
                                       text-slate-700 mb-1.5"
                            >
                                Photo du produit
                            </label>


                            <div
                                class="border-2 border-dashed border-gray-200
                                       rounded-xl p-4 flex items-center gap-4
                                       bg-white"
                            >

                                {{-- APERCU --}}

                                <div
                                    id="photo-preview"
                                    class="h-14 w-14 rounded-lg bg-gray-50
                                           border border-gray-100 flex items-center
                                           justify-center text-slate-400
                                           shrink-0 overflow-hidden"
                                >

                                    @if(isset($product) && $product->photo)

                                        <img
                                            src="{{ asset('storage/' . $product->photo) }}"
                                            alt="{{ $product->name }}"
                                            class="w-full h-full object-contain p-1"
                                        >

                                    @else

                                        <i
                                            data-lucide="image"
                                            class="w-6 h-6"
                                        ></i>

                                    @endif

                                </div>


                                {{-- CHOIX FICHIER --}}

                                <div class="flex-1">

                                    <div class="flex items-center gap-3">

                                        <label
                                            for="photo"
                                            class="cursor-pointer px-3 py-1.5
                                                   rounded-lg border border-gray-200
                                                   text-xs font-medium text-slate-700
                                                   hover:bg-gray-50 transition-colors"
                                        >
                                            Choisir un fichier
                                        </label>


                                        <span
                                            id="file-name"
                                            class="text-xs text-slate-muted"
                                        >
                                            Aucun fichier choisi
                                        </span>


                                        <input
                                            type="file"
                                            id="photo"
                                            name="photo"
                                            accept=".jpg,.jpeg,.png,.webp,.svg"
                                            class="hidden"
                                        >

                                    </div>


                                    <p class="text-[11px] text-slate-muted mt-1.5">
                                        JPG, PNG, WebP ou SVG - 2 Mo maximum
                                    </p>


                                    @error('photo')

                                        <p class="text-xs text-red-500 mt-1">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- STOCK --}}
                        {{-- ================================================= --}}

                        <div
                            class="p-4 rounded-xl border border-gray-100
                                   bg-gray-50/50 flex items-start gap-3"
                        >

                            <input
                                type="checkbox"
                                id="stock"
                                name="stock"
                                value="1"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300
                                       text-emerald-600 focus:ring-emerald-500"
                                {{ old(
                                    'stock',
                                    $product->stock ?? true
                                ) ? 'checked' : '' }}
                            >


                            <div>

                                <label
                                    for="stock"
                                    class="text-xs font-semibold
                                           text-slate-900 block"
                                >
                                    Disponible en stock
                                </label>


                                <p class="text-[11px] text-slate-muted">
                                    Décoché, le produit s'affiche comme
                                    « Sur commande ».
                                </p>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- BOUTONS --}}
                        {{-- ================================================= --}}

                        <div class="flex gap-3">

                            @if(isset($product))

                                <a
                                    href="{{ route('produits.index') }}"
                                    class="flex-1 py-3 rounded-xl border
                                           border-gray-200 text-slate-700
                                           font-medium text-sm flex items-center
                                           justify-center hover:bg-gray-50"
                                >
                                    Annuler
                                </a>

                            @endif


                            <button
                                type="submit"
                                class="{{ isset($product)
                                    ? 'flex-1'
                                    : 'w-full'
                                }} py-3 rounded-xl bg-emerald-600
                                hover:bg-emerald-700 text-white font-medium
                                text-sm transition-colors flex items-center
                                justify-center gap-2 shadow-sm"
                            >

                                <i
                                    data-lucide="{{ isset($product)
                                        ? 'save'
                                        : 'plus'
                                    }}"
                                    class="w-4 h-4"
                                ></i>


                                {{ isset($product)
                                    ? 'Modifier le produit'
                                    : 'Ajouter le produit'
                                }}

                            </button>

                        </div>

                    </form>

                </div>


                {{-- ===================================================== --}}
                {{-- LISTE DES PRODUITS --}}
                {{-- ===================================================== --}}

                <div
                    class="lg:col-span-5 bg-white rounded-2xl
                           border border-gray-100 p-6 shadow-sm"
                 >


                    {{-- EN-TÊTE --}}

                    <div class="flex items-center justify-between mb-6">

                        <h3 class="font-bold text-slate-900 text-base">
                            Produits ({{ $products->count() }})
                        </h3>

                    </div>

                     <input
                            id="searchInput"
                            type="text"
                            placeholder="Rechercher un produit, prix ou categorie"
                            class="w-full pl-10 pr-4 py-2.5
                                   rounded-xl
                                   border border-gray-200
                                   text-xs outline-none
                                   focus:border-emerald-600
                                   focus:ring-2
                                   focus:ring-emerald-100">


                    {{-- LISTE SCROLLABLE --}}

                    <div
                        class="max-h-[calc(100vh-250px)] overflow-y-auto
                               pr-2 space-y-3"
                     >

                        @forelse($products as $productItem)

                            {{-- PRODUIT --}}

                            <div
                                class="border border-gray-100 rounded-xl p-4
                                       hover:shadow-sm transition"
                            >

                                <div class="flex items-center gap-3">


                                    {{-- PHOTO --}}

                                    <div
                                        class="h-16 w-16 rounded-xl bg-gray-50
                                               border border-gray-100 flex items-center
                                               justify-center overflow-hidden shrink-0"
                                    >

                                        @if($productItem->photo)

                                            <img
                                                src="{{ asset('storage/' . $productItem->photo) }}"
                                                alt="{{ $productItem->name }}"
                                                class="w-full h-full object-contain p-1"
                                            >

                                        @else

                                            <i
                                                data-lucide="package"
                                                class="w-6 h-6 text-slate-400"
                                            ></i>

                                        @endif

                                    </div>


                                    {{-- INFORMATIONS --}}

                                    <div class="flex-1 min-w-0">

                                        <h4
                                            class="font-bold text-slate-900
                                                   text-sm truncate"
                                        >
                                            {{ $productItem->name }}
                                        </h4>


                                        {{-- CATÉGORIE --}}

                                        <p class="text-[11px] text-slate-500 mt-0.5">

                                            {{ $productItem->sousCategorie->category->nom
                                                ?? 'Sans catégorie'
                                            }}

                                        </p>


                                        {{-- SOUS-CATÉGORIE --}}

                                        <p class="text-xs text-emerald-700 font-medium">

                                            {{ $productItem->sousCategorie->nom
                                                ?? 'Sans sous-catégorie'
                                            }}

                                        </p>


                                        {{-- PRIX --}}

                                        @if($productItem->price !== null)

                                            <p
                                                class="text-xs font-semibold
                                                       text-slate-700 mt-1"
                                             >

                                                {{ number_format(
                                                    $productItem->price,
                                                    0,
                                                    ',',
                                                    ' '
                                                ) }}

                                                FCFA

                                            </p>

                                        @else

                                            <p
                                                class="text-xs text-slate-muted mt-1"
                                            >
                                                Sur devis
                                            </p>

                                        @endif


                                        {{-- STOCK --}}

                                        @if($productItem->stock)

                                            <span
                                                class="inline-flex mt-1 px-2 py-0.5
                                                       rounded-full bg-green-100
                                                       text-green-700 text-[10px]
                                                       font-medium"
                                            >
                                                En stock
                                            </span>

                                        @else

                                            <span
                                                class="inline-flex mt-1 px-2 py-0.5
                                                       rounded-full bg-orange-100
                                                       text-orange-700 text-[10px]
                                                       font-medium"
                                            >
                                                Sur commande
                                            </span>

                                        @endif

                                    </div>


                                    {{-- ACTIONS --}}

                                    <div
                                        class="flex items-center gap-1 shrink-0"
                                    >

                                        {{-- MODIFIER --}}

                                        <a
                                            href="{{ route(
                                                'produits.edit',
                                                $productItem->id
                                            ) }}"
                                            class="p-2 rounded-lg text-slate-500
                                                   hover:bg-gray-100
                                                   hover:text-emerald-700"
                                            title="Modifier"
                                        >

                                            <i
                                                data-lucide="pencil"
                                                class="w-4 h-4"
                                            ></i>

                                        </a>


                                        {{-- SUPPRIMER --}}

                                        <form
                                            action="{{ route(
                                                'produits.destroy',
                                                $productItem->id
                                            ) }}"
                                            method="POST"
                                            onsubmit="return confirm(
                                                'Voulez-vous vraiment supprimer ce produit ?'
                                            );"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="p-2 rounded-lg text-slate-500
                                                       hover:bg-red-50
                                                       hover:text-red-600"
                                                title="Supprimer"
                                            >

                                                <i
                                                    data-lucide="trash-2"
                                                    class="w-4 h-4"
                                                ></i>

                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        @empty

                            {{-- AUCUN PRODUIT --}}

                            <div
                                class="border-2 border-dashed border-gray-200
                                       rounded-2xl p-12 text-center
                                       flex flex-col items-center
                                       justify-center"
                            >

                                <div
                                    class="h-10 w-10 rounded-full bg-forest-50
                                           text-forest-700 flex items-center
                                           justify-center mb-3"
                                >

                                    <i
                                        data-lucide="package"
                                        class="w-5 h-5"
                                    ></i>

                                </div>

                                <h4 class="font-bold text-slate-900 text-sm">
                                    Aucun produit
                                </h4>

                                <p class="text-xs text-slate-muted mt-1">
                                    Ajoutez votre premier produit au catalogue.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


{{-- ============================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ============================================================= --}}

<script>

    lucide.createIcons();


    // =============================================================
    // NOM DU FICHIER PDF DE LA FICHE TECHNIQUE
    // =============================================================

    const ficheTechniqueInput = document.getElementById('fiche_technique');
    const ficheTechniqueName = document.getElementById('fiche-technique-name');

    if (ficheTechniqueInput && ficheTechniqueName) {
        ficheTechniqueInput.addEventListener('change', function () {
            ficheTechniqueName.textContent =
                this.files && this.files.length > 0
                    ? this.files[0].name
                    : 'Aucun fichier choisi';
        });
    }


    // =============================================================
    // APERÇU PHOTO
    // =============================================================

    const photoInput = document.getElementById('photo');
    const fileName = document.getElementById('file-name');
    const photoPreview = document.getElementById('photo-preview');


    if (photoInput) {

        photoInput.addEventListener('change', function () {

            if (this.files && this.files.length > 0) {

                const file = this.files[0];

                fileName.textContent = file.name;


                const reader = new FileReader();


                reader.onload = function (event) {

                    photoPreview.innerHTML = `
                        <img
                            src="${event.target.result}"
                            alt="Aperçu"
                            class="w-full h-full object-contain p-1"
                        >
                    `;

                };


                reader.readAsDataURL(file);

            } else {

                fileName.textContent = 'Aucun fichier choisi';

            }

        });

    }

</script>


</body>
</html>
