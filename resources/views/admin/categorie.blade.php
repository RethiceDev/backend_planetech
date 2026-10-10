<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Planetech Administration - Catégories</title>

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

    <script src="https://unpkg.com/lucide@latest"></script>
</head>


<body class="bg-gray-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    @include('admin.aside')


    <!-- CONTENU PRINCIPAL -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">



        <!-- PAGE -->
        <main class="p-8 space-y-6">


            <!-- MESSAGE SUCCÈS -->
            @if(session('success'))

                <div
                    class="px-4 py-3 rounded-xl bg-green-100 border border-green-300 text-green-700 font-medium text-sm"
                >
                    {{ session('success') }}
                </div>

            @endif


            <!-- MESSAGE ERREUR -->
            @if(session('error'))

                <div
                    class="px-4 py-3 rounded-xl bg-red-100 border border-red-300 text-red-700 font-medium text-sm"
                >
                    {{ session('error') }}
                </div>

            @endif


            <!-- ERREURS VALIDATION -->
            @if($errors->any())

                <div
                    class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"
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


            <!-- BOUTON CATÉGORIES -->
            <div class="flex items-center gap-3">

                        <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="tags" class="w-5 h-5"></i>
                        </div>

                       <div>

                            <h1 class="text-2xl font-bold text-slate-900">
                                Toutes les categories
                            </h1>

                            <p class="text-sm text-slate-muted mt-1">
                                Consultez les categories des produits.
                            </p>

                        </div>
            </div>


            <!-- GRILLE -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">


                <!-- ========================= -->
                <!-- FORMULAIRE -->
                <!-- ========================= -->

                <div
                    class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm"
                >

                    <div class="mb-6">

                        <h3 class="font-bold text-slate-900 text-base">

                            {{ isset($category)
                                ? 'Modifier la catégorie'
                                : 'Ajouter une catégorie'
                            }}

                        </h3>


                        <p class="text-xs text-slate-muted mt-1">

                            {{ isset($category)
                                ? 'Modifiez le nom de la catégorie.'
                                : 'Créez une catégorie pour organiser vos produits.'
                            }}

                        </p>

                    </div>


                    <!-- FORMULAIRE -->

                    <form
                        action="{{ isset($category)
                            ? route('categories.update', $category->id)
                            : route('categories.store')
                        }}"
                        method="POST"
                        class="space-y-5"
                    >

                        @csrf


                        @if(isset($category))

                            @method('PUT')

                        @endif


                        <!-- NOM -->

                        <div>

                            <label
                                for="nom"
                                class="block text-xs font-semibold text-slate-700 mb-1.5"
                            >

                                Nom de la catégorie

                                <span class="text-red-500">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="nom"
                                name="nom"
                                value="{{ old('nom', $category->nom ?? '') }}"
                                placeholder="Ex. Matériel informatique"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white"
                                required
                            >


                            @error('nom')

                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        <!-- BOUTONS -->

                        <div class="flex gap-3">


                            @if(isset($category))

                                <a
                                    href="{{ route('categories.index') }}"
                                    class="flex-1 py-3 rounded-xl border border-gray-200 text-slate-700 font-medium text-sm flex items-center justify-center hover:bg-gray-50"
                                >
                                    Annuler
                                </a>

                            @endif


                            <button
                                type="submit"
                                class="{{ isset($category) ? 'flex-1' : 'w-full' }} py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm flex items-center justify-center gap-2 shadow-sm"
                            >

                                <i
                                    data-lucide="{{ isset($category) ? 'save' : 'plus' }}"
                                    class="w-4 h-4"
                                ></i>


                                {{ isset($category)
                                    ? 'Modifier la catégorie'
                                    : 'Ajouter la catégorie'
                                }}

                            </button>

                        </div>

                    </form>

                </div>


                <!-- ========================= -->
                <!-- LISTE CATÉGORIES -->
                <!-- ========================= -->

                <div
                    class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm"
                >


                    <div
                        class="flex items-center justify-between mb-6"
                    >

                        <h3 class="font-bold text-slate-900 text-base">

                            Catégories ({{ $categories->count() }})

                        </h3>

                    </div>


                    @forelse($categories as $categoryItem)


                        <div
                            class="border border-gray-100 rounded-xl p-4 mb-3 flex items-center justify-between hover:shadow-sm transition"
                        >


                            <!-- NOM -->

                            <div class="flex items-center gap-3">

                                <div
                                    class="h-10 w-10 rounded-xl bg-forest-50 text-forest-700 flex items-center justify-center"
                                >

                                    <i
                                        data-lucide="tag"
                                        class="w-5 h-5"
                                    ></i>

                                </div>


                                <div>

                                    <h4
                                        class="font-bold text-slate-900 text-sm"
                                    >
                                        {{ $categoryItem->nom }}
                                    </h4>


                                    <p
                                        class="text-xs text-slate-muted"
                                    >

                                        {{ $categoryItem->produits_count }}

                                        produit(s)

                                    </p>

                                </div>

                            </div>


                            <!-- ACTIONS -->

                            <div class="flex items-center gap-1">


                                <!-- MODIFIER -->

                                <a
                                    href="{{ route('categories.edit', $categoryItem->id) }}"
                                    class="p-2 rounded-lg text-slate-500 hover:bg-gray-100 hover:text-emerald-700"
                                    title="Modifier"
                                >

                                    <i
                                        data-lucide="pencil"
                                        class="w-4 h-4"
                                    ></i>

                                </a>


                                <!-- SUPPRIMER -->

                                <form
                                    action="{{ route('categories.destroy', $categoryItem->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Voulez-vous vraiment supprimer cette catégorie ?');"
                                >

                                    @csrf

                                    @method('DELETE')


                                    <button
                                        type="submit"
                                        class="p-2 rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600"
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


                    @empty


                        <!-- AUCUNE CATÉGORIE -->

                        <div
                            class="border-2 border-dashed border-gray-200 rounded-2xl p-12 text-center flex flex-col items-center justify-center"
                        >

                            <div
                                class="h-10 w-10 rounded-full bg-forest-50 text-forest-700 flex items-center justify-center mb-3"
                            >

                                <i
                                    data-lucide="tags"
                                    class="w-5 h-5"
                                ></i>

                            </div>


                            <h4
                                class="font-bold text-slate-900 text-sm"
                            >
                                Aucune catégorie
                            </h4>


                            <p
                                class="text-xs text-slate-muted mt-1"
                            >
                                Ajoutez votre première catégorie.
                            </p>

                        </div>


                    @endforelse

                </div>

            </div>

        </main>

    </div>

</div>


<script>
    lucide.createIcons();
</script>

</body>
</html>
