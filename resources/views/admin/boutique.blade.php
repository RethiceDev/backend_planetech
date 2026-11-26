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

@php
    // Gère : URL externe, "storage/products/x.jpg" (scraping) et "products/x.jpg" (upload admin)
    $imgUrl = function ($path) {
        if (!$path) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        return \Illuminate\Support\Str::startsWith($path, 'storage/')
            ? asset($path)
            : asset('storage/' . $path);
    };
@endphp

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    @include('admin.aside')

    {{-- CONTENU PRINCIPAL --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto overflow-x-hidden">

        <main class="p-8 space-y-6">

            {{-- MESSAGE SUCCÈS --}}
            @if(session('success'))
                <div class="px-4 py-3 rounded-xl bg-green-100 border border-green-300 text-green-700 font-medium text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- MESSAGE ERREUR --}}
            @if(session('error'))
                <div class="px-4 py-3 rounded-xl bg-red-100 border border-red-300 text-red-700 font-medium text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ERREURS VALIDATION --}}
            @if($errors->any())
                <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- TITRE --}}
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <i data-lucide="store" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Boutique</h1>
                    <p class="text-sm text-slate-muted mt-1">Consultez les produits de la Boutique.</p>
                </div>
            </div>

            {{-- GRILLE PRINCIPALE --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- ===================== FORMULAIRE PRODUIT ===================== --}}
                <div class="lg:col-span-7 min-w-0 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">

                    <div class="mb-6">
                        <h3 class="font-bold text-slate-900 text-base">
                            {{ isset($product) ? 'Modifier le produit' : 'Ajouter un produit' }}
                        </h3>
                        <p class="text-xs text-slate-muted mt-1">
                            {{ isset($product)
                                ? 'Modifiez les informations du produit.'
                                : 'Le produit apparaîtra dans le catalogue public.' }}
                        </p>
                    </div>

                    <form
                        action="{{ isset($product) ? route('produits.update', $product->id) : route('produits.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-5"
                    >
                        @csrf

                        @if(isset($product))
                            @method('PUT')
                        @endif

                        {{-- NOM --}}
                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Nom du produit <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $product->name ?? '') }}"
                                placeholder="Ex. Switch 24 ports Gigabit"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white"
                                required
                            >

                            @error('name')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- SOUS-CATEGORIE + PRIX --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div>
                                <label for="sous_categorie_id" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Sous-catégorie <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <select
                                        id="sous_categorie_id"
                                        name="sous_categorie_id"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white appearance-none pr-10"
                                        required
                                    >
                                        <option value="">Sélectionner une sous-catégorie</option>

                                        @foreach($sousCategories as $sousCategorie)
                                            <option
                                                value="{{ $sousCategorie->id }}"
                                                @selected(old('sous_categorie_id', $product->sous_categorie_id ?? '') == $sousCategorie->id)
                                            >
                                                {{ $sousCategorie->category->nom ?? 'Sans catégorie' }} — {{ $sousCategorie->nom }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <i data-lucide="chevron-down"
                                       class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                </div>

                                @error('sous_categorie_id')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="price" class="block text-xs font-semibold text-slate-700 mb-1.5">
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
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white"
                                >

                                <p class="text-[11px] text-slate-muted mt-1">
                                    Laissez vide pour afficher « Sur devis ».
                                </p>

                                @error('price')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- DESCRIPTION --}}
                        <div>
                            <label for="description" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Description <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Caractéristiques principales, usage recommandé, garantie..."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white resize-none"
                                required
                            >{{ old('description', $product->description ?? '') }}</textarea>

                            @error('description')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- FICHE TECHNIQUE PDF --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Fiche technique PDF
                                <span class="text-slate-400 font-normal">(Facultatif)</span>
                            </label>

                            <div class="border border-gray-200 rounded-xl p-4 bg-white">
                                @if(isset($product) && $product->fiche_technique)
                                    <div class="flex items-center gap-2 mb-3 text-sm text-emerald-700">
                                        <i data-lucide="file-text" class="w-5 h-5"></i>
                                        <a
                                            href="{{ $imgUrl($product->fiche_technique) }}"
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
                                    Format PDF uniquement, 5 Mo maximum.
                                </p>

                                @error('fiche_technique')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- PHOTO --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Photo du produit
                            </label>

                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 flex items-center gap-4 bg-white">

                                <div
                                    id="photo-preview"
                                    class="h-14 w-14 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center text-slate-400 shrink-0 overflow-hidden"
                                >
                                    @if(isset($product) && $product->photo)
                                        <img
                                            src="{{ $imgUrl($product->photo) }}"
                                            alt="{{ $product->name }}"
                                            class="w-full h-full object-contain p-1"
                                        >
                                    @else
                                        <i data-lucide="image" class="w-6 h-6"></i>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <label
                                            for="photo"
                                            class="cursor-pointer px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-slate-700 hover:bg-gray-50 transition-colors"
                                        >
                                            Choisir un fichier
                                        </label>

                                        <span id="file-name" class="text-xs text-slate-muted truncate">
                                            Aucun fichier choisi
                                        </span>

                                        <input
                                            type="file"
                                            id="photo"
                                            name="photo"
                                            accept=".jpg,.jpeg,.png,.webp"
                                            class="hidden"
                                        >
                                    </div>

                                    <p class="text-[11px] text-slate-muted mt-1.5">
                                        JPG, PNG ou WebP - 2 Mo maximum
                                    </p>

                                    @error('photo')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- STOCK --}}
                        <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 flex items-start gap-3">
                            <input
                                type="checkbox"
                                id="stock"
                                name="stock"
                                value="1"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                {{ old('stock', $product->stock ?? true) ? 'checked' : '' }}
                            >

                            <div>
                                <label for="stock" class="text-xs font-semibold text-slate-900 block">
                                    Disponible en stock
                                </label>
                                <p class="text-[11px] text-slate-muted">
                                    Décoché, le produit s'affiche comme « Sur commande ».
                                </p>
                            </div>
                        </div>

                        {{-- BOUTONS --}}
                        <div class="flex gap-3">
                            @if(isset($product))
                                <a
                                    href="{{ route('produits.index', request()->only('q', 'page')) }}"
                                    class="flex-1 py-3 rounded-xl border border-gray-200 text-slate-700 font-medium text-sm flex items-center justify-center hover:bg-gray-50"
                                >
                                    Annuler
                                </a>
                            @endif

                            <button
                                type="submit"
                                class="{{ isset($product) ? 'flex-1' : 'w-full' }} py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                            >
                                <i data-lucide="{{ isset($product) ? 'save' : 'plus' }}" class="w-4 h-4"></i>
                                {{ isset($product) ? 'Modifier le produit' : 'Ajouter le produit' }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ===================== LISTE DES PRODUITS ===================== --}}
                <div class="lg:col-span-5 min-w-0 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">

                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-900 text-base">
                            Produits ({{ $products->total() }})
                        </h3>
                    </div>

                    {{-- RECHERCHE (côté serveur, compatible pagination) --}}
                    <form id="searchForm" method="GET" action="{{ route('produits.index') }}" class="mb-4">

                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <i data-lucide="search"
                                class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>

                                <input
                                    id="searchInput"
                                    type="text"
                                    name="q"
                                    value="{{ $search ?? '' }}"
                                    placeholder="Rechercher un produit, prix ou catégorie"
                                    autocomplete="off"
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                >
                            </div>

                            <button
                                type="submit"
                                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition-colors"
                            >
                                Rechercher
                            </button>
                        </div>

                        @if(!empty($search))
                            <p class="text-[11px] text-slate-muted mt-2">
                                {{ $products->total() }} résultat(s) pour « {{ $search }} » —
                                <a href="{{ route('produits.index') }}" class="underline">Effacer la recherche</a>
                            </p>
                        @endif
                    </form>

                    {{-- LISTE --}}
                    <div class="space-y-3">

                        @forelse($products as $productItem)

                            <div class="border border-gray-100 rounded-xl p-4 hover:shadow-sm transition">
                                <div class="flex items-center gap-3">

                                    {{-- PHOTO --}}
                                    <div class="h-16 w-16 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden shrink-0">
                                        @if($productItem->photo)
                                            <img
                                                src="{{ $imgUrl($productItem->photo) }}"
                                                alt="{{ $productItem->name }}"
                                                loading="lazy"
                                                decoding="async"
                                                class="w-full h-full object-contain p-1 text-[9px] text-slate-400 overflow-hidden"
                                            >
                                        @else
                                            <i data-lucide="package" class="w-6 h-6 text-slate-400"></i>
                                        @endif
                                    </div>

                                    {{-- INFORMATIONS --}}
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-slate-900 text-sm truncate">
                                            {{ $productItem->name }}
                                        </h4>

                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            {{ $productItem->sousCategorie->category->nom ?? 'Sans catégorie' }}
                                        </p>

                                        <p class="text-xs text-emerald-700 font-medium">
                                            {{ $productItem->sousCategorie->nom ?? 'Sans sous-catégorie' }}
                                        </p>

                                        @if($productItem->price !== null)
                                            <p class="text-xs font-semibold text-slate-700 mt-1">
                                                {{ number_format($productItem->price, 0, ',', ' ') }} FCFA
                                            </p>
                                        @else
                                            <p class="text-xs text-slate-muted mt-1">Sur devis</p>
                                        @endif

                                        @if($productItem->stock)
                                            <span class="inline-flex mt-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-medium">
                                                En stock
                                            </span>
                                        @else
                                            <span class="inline-flex mt-1 px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-[10px] font-medium">
                                                Sur commande
                                            </span>
                                        @endif
                                    </div>

                                    {{-- ACTIONS --}}
                                    <div class="flex items-center gap-1 shrink-0">

                                        <a
                                            href="{{ route('produits.edit', ['product' => $productItem->id] + request()->only('q', 'page')) }}"
                                            class="p-2 rounded-lg text-slate-500 hover:bg-gray-100 hover:text-emerald-700"
                                            title="Modifier"
                                        >
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>

                                        <form
                                            action="{{ route('produits.destroy', $productItem->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Voulez-vous vraiment supprimer ce produit ?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="p-2 rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600"
                                                title="Supprimer"
                                            >
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        @empty

                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-12 text-center flex flex-col items-center justify-center">
                                <div class="h-10 w-10 rounded-full bg-forest-50 text-forest-700 flex items-center justify-center mb-3">
                                    <i data-lucide="package" class="w-5 h-5"></i>
                                </div>

                                <h4 class="font-bold text-slate-900 text-sm">Aucun produit</h4>

                                <p class="text-xs text-slate-muted mt-1">
                                    {{ !empty($search)
                                        ? 'Aucun résultat pour cette recherche.'
                                        : 'Ajoutez votre premier produit au catalogue.' }}
                                </p>
                            </div>

                        @endforelse
                    </div>

                    {{-- PAGINATION --}}
                    @if($products->hasPages())
                        <div class="mt-6">
                            {{ $products->links('vendor.pagination.admin') }}
                        </div>
                    @endif

                </div>
            </div>
        </main>
    </div>
</div>

<script>
    lucide.createIcons();

    // Nom du fichier PDF
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

    // Aperçu photo
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
                    photoPreview.innerHTML =
                        `<img src="${event.target.result}" alt="Aperçu" class="w-full h-full object-contain p-1">`;
                };
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'Aucun fichier choisi';
            }
        });
    }



    // Recherche automatique après 0,6 s sans frappe
const searchForm  = document.getElementById('searchForm');
const searchInput = document.getElementById('searchInput');

if (searchForm && searchInput) {
    let searchTimer;

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => searchForm.submit(), 600);
    });

    // Après le rechargement, remet le curseur à la fin du texte
    if (searchInput.value) {
        searchInput.focus();
        const end = searchInput.value.length;
        searchInput.setSelectionRange(end, end);
    }
}
</script>

</body>
</html>
