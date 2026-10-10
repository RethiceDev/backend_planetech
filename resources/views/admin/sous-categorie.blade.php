<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Planetech Administration - Sous-catégories</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
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
                        },
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

    <!-- ================================================= -->
    <!-- SIDEBAR -->
    <!-- ================================================= -->

    @include('admin.aside')


    <!-- ================================================= -->
    <!-- MAIN -->
    <!-- ================================================= -->

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        <main class="p-6 lg:p-8 space-y-6">

            <!-- ================================================= -->
            <!-- HEADER -->
            <!-- ================================================= -->

             <div class="flex items-center gap-3">

                        <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="list-tree" class="w-5 h-5"></i>
                        </div>

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Sous-catégories
                    </h1>

                    <p class="text-sm text-slate-500 mt-1">
                        Organisez les produits de votre catalogue par sous-catégories.
                    </p>
                </div>

                

            </div>


            <!-- ================================================= -->
            <!-- MESSAGES -->
            <!-- ================================================= -->

            @if(session('success'))

                <div class="flex items-center gap-3
                            bg-emerald-50 border border-emerald-200
                            text-emerald-700 rounded-xl px-4 py-3">

                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>

                    <p class="text-xs font-medium">
                        {{ session('success') }}
                    </p>

                </div>

            @endif


            @if(session('error'))

                <div class="flex items-center gap-3
                            bg-red-50 border border-red-200
                            text-red-700 rounded-xl px-4 py-3">

                    <i data-lucide="circle-alert" class="w-5 h-5"></i>

                    <p class="text-xs font-medium">
                        {{ session('error') }}
                    </p>

                </div>

            @endif


            @if($errors->any())

                <div class="bg-red-50 border border-red-200
                            text-red-700 rounded-xl px-4 py-3">

                    <div class="flex items-start gap-3">

                        <i data-lucide="circle-alert"
                           class="w-5 h-5 mt-0.5 shrink-0"></i>

                        <div>

                            <p class="text-xs font-semibold mb-1">
                                Veuillez corriger les erreurs suivantes :
                            </p>

                            <ul class="text-xs space-y-1 list-disc list-inside">

                                @foreach($errors->all() as $error)

                                    <li>{{ $error }}</li>

                                @endforeach

                            </ul>

                        </div>

                    </div>

                </div>

            @endif


            <!-- ================================================= -->
            <!-- STATISTIQUES -->
            <!-- ================================================= -->

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">

                <!-- Total sous-catégories -->

                <div class="bg-white border border-gray-100
                            rounded-2xl p-5 shadow-sm">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs text-slate-400">
                                Total sous-catégories
                            </p>

                            <p class="text-2xl font-bold text-slate-900 mt-2">
                                {{ $sousCategories->count() }}
                            </p>

                            <p class="text-[10px] text-slate-400 mt-1">
                                Sous-catégories enregistrées
                            </p>

                        </div>

                        <div class="h-11 w-11 rounded-xl
                                    bg-emerald-50 text-emerald-700
                                    flex items-center justify-center">

                            <i data-lucide="layers" class="w-5 h-5"></i>

                        </div>

                    </div>

                </div>


                <!-- Catégories -->

                <div class="bg-white border border-gray-100
                            rounded-2xl p-5 shadow-sm">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs text-slate-400">
                                Catégories
                            </p>

                            <p class="text-2xl font-bold text-slate-900 mt-2">
                                {{ $categories->count() }}
                            </p>

                            <p class="text-[10px] text-blue-500 mt-1">
                                Catégories disponibles
                            </p>

                        </div>

                        <div class="h-11 w-11 rounded-xl
                                    bg-blue-50 text-blue-600
                                    flex items-center justify-center">

                            <i data-lucide="folder-tree" class="w-5 h-5"></i>

                        </div>

                    </div>

                </div>


                <!-- Produits concernés -->

                <div class="bg-white border border-gray-100
                            rounded-2xl p-5 shadow-sm">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-xs text-slate-400">
                                Produits associés
                            </p>

                            <p class="text-2xl font-bold text-slate-900 mt-2">

                                {{ $sousCategories->sum('produits_count') }}

                            </p>

                            <p class="text-[10px] text-amber-600 mt-1">
                                Produits classés
                            </p>

                        </div>

                        <div class="h-11 w-11 rounded-xl
                                    bg-amber-50 text-amber-600
                                    flex items-center justify-center">

                            <i data-lucide="package" class="w-5 h-5"></i>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ================================================= -->
            <!-- FORMULAIRE -->
            <!-- ================================================= -->

            <div class="bg-white border border-gray-100
                        rounded-2xl shadow-sm p-6">

                <div class="flex items-center gap-3 mb-6">

                    <div class="h-10 w-10 rounded-xl
                                bg-emerald-50 text-emerald-700
                                flex items-center justify-center">

                        <i
                            data-lucide="{{ isset($sousCategorie) ? 'pencil' : 'plus' }}"
                            class="w-5 h-5">
                        </i>

                    </div>

                    <div>

                        <h2 class="font-bold text-slate-900 text-sm">

                            {{ isset($sousCategorie)
                                ? 'Modifier la sous-catégorie'
                                : 'Ajouter une sous-catégorie'
                            }}

                        </h2>

                        <p class="text-xs text-slate-400 mt-1">

                            {{ isset($sousCategorie)
                                ? 'Modifiez les informations de cette sous-catégorie.'
                                : 'Associez une sous-catégorie à une catégorie.'
                            }}

                        </p>

                    </div>

                </div>


                <form
                    action="{{ isset($sousCategorie)
                        ? route('sous-categories.update', $sousCategorie->id)
                        : route('sous-categories.store')
                    }}"
                    method="POST"
                    class="space-y-5">

                    @csrf

                    @if(isset($sousCategorie))

                        @method('PUT')

                    @endif


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <!-- Catégorie -->

                        <div>

                            <label
                                for="category_id"
                                class="block text-xs font-semibold
                                       text-slate-700 mb-2">

                                Catégorie

                                <span class="text-red-500">*</span>

                            </label>

                            <div class="relative">

                                <i
                                    data-lucide="folder"
                                    class="absolute left-3.5 top-1/2
                                           -translate-y-1/2
                                           w-4 h-4 text-slate-400">
                                </i>

                                <select
                                    name="category_id"
                                    id="category_id"
                                    required
                                    class="w-full pl-10 pr-4 py-3
                                           rounded-xl
                                           border border-gray-200
                                           bg-white text-sm
                                           text-slate-700
                                           outline-none
                                           focus:border-emerald-600
                                           focus:ring-2
                                           focus:ring-emerald-100">

                                    <option value="">
                                        Sélectionner une catégorie
                                    </option>

                                    @foreach($categories as $category)

                                        <option
                                            value="{{ $category->id }}"
                                            {{ old(
                                                'category_id',
                                                isset($sousCategorie)
                                                    ? $sousCategorie->category_id
                                                    : ''
                                            ) == $category->id
                                                ? 'selected'
                                                : ''
                                            }}>

                                            {{ $category->nom }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        </div>


                        <!-- Nom -->

                        <div>

                            <label
                                for="nom"
                                class="block text-xs font-semibold
                                       text-slate-700 mb-2">

                                Nom de la sous-catégorie

                                <span class="text-red-500">*</span>

                            </label>

                            <div class="relative">

                                <i
                                    data-lucide="tag"
                                    class="absolute left-3.5 top-1/2
                                           -translate-y-1/2
                                           w-4 h-4 text-slate-400">
                                </i>

                                <input
                                    type="text"
                                    name="nom"
                                    id="nom"
                                    value="{{ old(
                                        'nom',
                                        isset($sousCategorie)
                                            ? $sousCategorie->nom
                                            : ''
                                    ) }}"
                                    placeholder="Ex. Imprimantes"
                                    required
                                    maxlength="255"
                                    class="w-full pl-10 pr-4 py-3
                                           rounded-xl
                                           border border-gray-200
                                           text-sm
                                           outline-none
                                           focus:border-emerald-600
                                           focus:ring-2
                                           focus:ring-emerald-100">

                            </div>

                        </div>

                    </div>


                    <!-- Boutons -->

                    <div class="flex flex-col sm:flex-row
                                items-center justify-end gap-3
                                pt-2">

                        @if(isset($sousCategorie))

                            <a
                                href="{{ route('sous-categories.index') }}"
                                class="w-full sm:w-auto
                                       inline-flex items-center
                                       justify-center gap-2
                                       px-4 py-2.5
                                       rounded-xl
                                       border border-gray-200
                                       bg-white
                                       text-slate-600
                                       text-xs font-semibold
                                       hover:bg-gray-50
                                       transition">

                                <i data-lucide="x" class="w-4 h-4"></i>

                                Annuler

                            </a>

                        @endif

                        <button
                            type="submit"
                            class="w-full sm:w-auto
                                   inline-flex items-center
                                   justify-center gap-2
                                   px-5 py-2.5
                                   rounded-xl
                                   bg-emerald-700
                                   hover:bg-emerald-800
                                   text-white
                                   text-xs font-semibold
                                   shadow-sm transition">

                            <i
                                data-lucide="{{ isset($sousCategorie)
                                    ? 'save'
                                    : 'plus'
                                }}"
                                class="w-4 h-4">
                            </i>

                            {{ isset($sousCategorie)
                                ? 'Enregistrer les modifications'
                                : 'Ajouter la sous-catégorie'
                            }}

                        </button>

                    </div>

                </form>

            </div>


            <!-- ================================================= -->
            <!-- RECHERCHE -->
            <!-- ================================================= -->

            <div class="bg-white border border-gray-100
                        rounded-2xl shadow-sm p-4">

                <div class="flex flex-col md:flex-row
                            md:items-center gap-3">

                    <div class="relative flex-1">

                        <i
                            data-lucide="search"
                            class="absolute left-3.5 top-1/2
                                   -translate-y-1/2
                                   w-4 h-4 text-slate-400">
                        </i>

                        <input
                            id="searchInput"
                            type="text"
                            placeholder="Rechercher une sous-catégorie ou une catégorie..."
                            class="w-full pl-10 pr-4 py-2.5
                                   rounded-xl
                                   border border-gray-200
                                   text-xs outline-none
                                   focus:border-emerald-600
                                   focus:ring-2
                                   focus:ring-emerald-100">

                    </div>

                    <div class="relative">

                        <i
                            data-lucide="filter"
                            class="absolute left-3.5 top-1/2
                                   -translate-y-1/2
                                   w-4 h-4 text-slate-400">
                        </i>

                        <select
                            id="categoryFilter"
                            class="w-full md:w-56
                                   pl-10 pr-8 py-2.5
                                   rounded-xl
                                   border border-gray-200
                                   text-xs outline-none
                                   focus:border-emerald-600
                                   focus:ring-2
                                   focus:ring-emerald-100">

                            <option value="">
                                Toutes les catégories
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}">
                                    {{ $category->nom }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

            </div>


            <!-- ================================================= -->
            <!-- TABLEAU -->
            <!-- ================================================= -->

            <div class="bg-white rounded-2xl border
                        border-gray-100 shadow-sm overflow-hidden">

                <!-- Header -->

                <div class="px-6 py-4 border-b
                            border-gray-100
                            flex items-center justify-between">

                    <div>

                        <h3 class="font-bold text-slate-900 text-sm">
                            Liste des sous-catégories
                        </h3>

                        <p class="text-xs text-slate-400 mt-1">

                            {{ $sousCategories->count() }}

                            sous-catégorie(s) enregistrée(s)

                        </p>

                    </div>

                </div>


                <!-- Table -->

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[800px]">

                        <thead>

                        <tr class="bg-gray-50/80
                                   border-b border-gray-100">

                            <th class="px-6 py-3 text-left
                                       text-[10px] uppercase
                                       tracking-wider
                                       text-slate-500 font-bold">
                                Sous-catégorie
                            </th>

                            <th class="px-4 py-3 text-left
                                       text-[10px] uppercase
                                       tracking-wider
                                       text-slate-500 font-bold">
                                Catégorie
                            </th>

                            <th class="px-4 py-3 text-left
                                       text-[10px] uppercase
                                       tracking-wider
                                       text-slate-500 font-bold">
                                Produits
                            </th>

                            <th class="px-4 py-3 text-left
                                       text-[10px] uppercase
                                       tracking-wider
                                       text-slate-500 font-bold">
                                Date
                            </th>

                            <th class="px-4 py-3 text-right
                                       text-[10px] uppercase
                                       tracking-wider
                                       text-slate-500 font-bold">
                                Actions
                            </th>

                        </tr>

                        </thead>


                        <tbody id="subcategoriesBody"
                               class="divide-y divide-gray-100">

                        @forelse($sousCategories as $item)

                            <tr
                                class="subcategory-row
                                       hover:bg-gray-50/70
                                       transition"
                                data-search="{{ strtolower(
                                    $item->nom . ' ' .
                                    ($item->category->nom ?? '')
                                ) }}"
                                data-category="{{ $item->category_id }}">

                                <!-- Sous-catégorie -->

                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="h-9 w-9 rounded-xl
                                                   bg-emerald-50
                                                   text-emerald-700
                                                   flex items-center
                                                   justify-center">

                                            <i
                                                data-lucide="tag"
                                                class="w-4 h-4">
                                            </i>

                                        </div>

                                        <div>

                                            <p class="text-xs font-bold
                                                      text-slate-900">

                                                {{ $item->nom }}

                                            </p>

                                            <p class="text-[10px]
                                                      text-slate-400 mt-1">

                                                ID #{{ $item->id }}

                                            </p>

                                        </div>

                                    </div>

                                </td>


                                <!-- Catégorie -->

                                <td class="px-4 py-4">

                                    <span
                                        class="inline-flex items-center
                                               gap-1.5
                                               px-2.5 py-1.5
                                               rounded-lg
                                               bg-blue-50
                                               text-blue-700
                                               text-[10px]
                                               font-semibold">

                                        <i
                                            data-lucide="folder"
                                            class="w-3 h-3">
                                        </i>

                                        {{ $item->category->nom ?? '—' }}

                                    </span>

                                </td>


                                <!-- Produits -->

                                <td class="px-4 py-4">

                                    <div class="flex items-center gap-2">

                                        <span
                                            class="text-xs font-semibold
                                                   text-slate-700">

                                            {{ $item->produits_count }}

                                        </span>

                                        <span
                                            class="text-[10px]
                                                   text-slate-400">

                                            produit(s)

                                        </span>

                                    </div>

                                </td>


                                <!-- Date -->

                                <td class="px-4 py-4">

                                    <p class="text-xs font-medium
                                              text-slate-700">

                                        {{ $item->created_at
                                            ? $item->created_at->format('d/m/Y')
                                            : '—'
                                        }}

                                    </p>

                                    <p class="text-[10px]
                                              text-slate-400 mt-1">

                                        {{ $item->created_at
                                            ? $item->created_at->format('H:i')
                                            : ''
                                        }}

                                    </p>

                                </td>


                                <!-- Actions -->

                                <td class="px-4 py-4">

                                    <div class="flex items-center
                                                justify-end gap-2">

                                        <!-- Modifier -->

                                        <a
                                            href="{{ route(
                                                'sous-categories.edit',
                                                $item->id
                                            ) }}"
                                            title="Modifier"
                                            class="h-8 w-8 rounded-lg
                                                   border border-gray-200
                                                   text-slate-500
                                                   hover:bg-emerald-50
                                                   hover:text-emerald-700
                                                   hover:border-emerald-200
                                                   flex items-center
                                                   justify-center
                                                   transition">

                                            <i
                                                data-lucide="pencil"
                                                class="w-4 h-4">
                                            </i>

                                        </a>


                                        <!-- Supprimer -->

                                        <form
                                            action="{{ route(
                                                'sous-categories.destroy',
                                                $item->id
                                            ) }}"
                                            method="POST"
                                            onsubmit="return confirm(
                                                'Voulez-vous vraiment supprimer cette sous-catégorie ?'
                                            );">

                                            @csrf

                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                title="Supprimer"
                                                class="h-8 w-8 rounded-lg
                                                       border border-gray-200
                                                       text-slate-500
                                                       hover:bg-red-50
                                                       hover:text-red-600
                                                       hover:border-red-200
                                                       flex items-center
                                                       justify-center
                                                       transition">

                                                <i
                                                    data-lucide="trash-2"
                                                    class="w-4 h-4">
                                                </i>

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center">

                                    <div class="flex flex-col
                                                items-center">

                                        <div
                                            class="h-14 w-14 rounded-2xl
                                                   bg-gray-100
                                                   text-slate-400
                                                   flex items-center
                                                   justify-center mb-4">

                                            <i
                                                data-lucide="layers"
                                                class="w-7 h-7">
                                            </i>

                                        </div>

                                        <p class="text-sm font-semibold
                                                  text-slate-700">

                                            Aucune sous-catégorie

                                        </p>

                                        <p class="text-xs text-slate-400 mt-1">

                                            Commencez par créer votre
                                            première sous-catégorie.

                                        </p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>


                <!-- Footer -->

                @if($sousCategories->count() > 0)

                    <div
                        class="px-6 py-4 border-t border-gray-100
                               flex flex-col sm:flex-row
                               items-center justify-between gap-3">

                        <p class="text-xs text-slate-400">

                            Affichage de

                            <span class="font-semibold text-slate-600">
                                <span id="visibleCount">
                                    {{ $sousCategories->count() }}
                                </span>
                            </span>

                            sous-catégorie(s)

                        </p>

                        <span class="text-[10px] text-slate-400">
                            Gestion du catalogue
                        </span>

                    </div>

                @endif

            </div>

        </main>

    </div>

</div>


<!-- ================================================= -->
<!-- JAVASCRIPT -->
<!-- ================================================= -->

<script>

    lucide.createIcons();


    /* =================================================
       RECHERCHE
    ================================================= */

    const searchInput =
        document.getElementById('searchInput');

    const categoryFilter =
        document.getElementById('categoryFilter');

    const rows =
        document.querySelectorAll('.subcategory-row');

    const visibleCount =
        document.getElementById('visibleCount');


    function filterSubcategories() {

        const search =
            searchInput.value.toLowerCase().trim();

        const category =
            categoryFilter.value;

        let count = 0;


        rows.forEach(row => {

            const content =
                row.dataset.search.toLowerCase();

            const rowCategory =
                row.dataset.category;


            const matchesSearch =
                content.includes(search);

            const matchesCategory =
                !category ||
                rowCategory === category;


            if (matchesSearch && matchesCategory) {

                row.classList.remove('hidden');

                count++;

            } else {

                row.classList.add('hidden');

            }

        });


        if (visibleCount) {
            visibleCount.textContent = count;
        }

    }


    searchInput.addEventListener(
        'input',
        filterSubcategories
    );


    categoryFilter.addEventListener(
        'change',
        filterSubcategories
    );

</script>

</body>
</html>
