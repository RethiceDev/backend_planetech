<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planetech Administration - Expertises</title>
    <!-- Tailwind CSS CDN -->
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
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 font-sans text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR -->
              @include('admin.aside')


        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

            <!-- TOPBAR -->
           

            <!-- PAGE CONTENT -->
            <main class="p-8">

                    <button class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-medium text-xs flex items-center gap-2 shadow-sm">
                         <i data-lucide="wrench" class="w-5 h-5"></i>
                        Domaines
                    </button>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    {{-- ========================================================= --}}
    {{-- COLONNE GAUCHE : FORMULAIRE                               --}}
    {{-- ========================================================= --}}

    <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">

        <div>
            <h3 class="font-bold text-slate-900 text-base">
                {{ isset($domaine) ? 'Modifier le domaine' : 'Ajouter un domaine' }}
            </h3>

            <p class="text-xs text-slate-muted mt-0.5">
                Ces blocs alimentent la section « Nos métiers » de la page d'accueil.
            </p>
        </div>


        {{-- MESSAGE DE SUCCÈS --}}
        @if(session('success'))
            <div class="px-4 py-3 rounded-xl bg-green-100 border border-green-300 text-green-700 font-medium text-sm">
                {{ session('success') }}
            </div>
        @endif


        {{-- ERREURS DE VALIDATION --}}
        @if($errors->any())
            <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                <p class="font-semibold mb-1">
                    Veuillez corriger les erreurs suivantes :
                </p>

                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- FORMULAIRE --}}
        <form
            action="{{ isset($domaine) ? route('domaines.update', $domaine->id) : route('domaines.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-5"
        >

            @csrf

            @if(isset($domaine))
                @method('PUT')
            @endif


            {{-- TITRE --}}
            <div>

                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Titre <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="titre"
                    value="{{ old('titre', $domaine->titre ?? '') }}"
                    placeholder="Ex. Télécom et réseau"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white"
                >

                @error('titre')
                    <p class="text-red-500 text-xs mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- DESCRIPTION --}}
            <div>

                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Description <span class="text-red-500">*</span>
                </label>

                <textarea
                    name="description"
                    rows="3"
                    placeholder="Une à deux phrases décrivant les prestations couvertes."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white resize-none"
                >{{ old('description', $domaine->description ?? '') }}</textarea>

                @error('description')
                    <p class="text-red-500 text-xs mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- VISUEL --}}
            <div>

                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Visuel (optionnel)
                </label>

                <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 bg-white">

                    <div class="flex items-center gap-4">

                        {{-- APERÇU --}}
                        <div class="h-14 w-14 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center text-slate-400 shrink-0 overflow-hidden">

                            @if(isset($domaine) && $domaine->visuel)

                                <img
                                    src="{{ asset('storage/' . $domaine->visuel) }}"
                                    alt="{{ $domaine->titre }}"
                                    class="w-full h-full object-cover"
                                >

                            @else

                                <i data-lucide="image" class="w-6 h-6"></i>

                            @endif

                        </div>


                        <div class="flex-1">

                            <div class="flex items-center gap-3">

                                <label
                                    for="visuel"
                                    class="cursor-pointer px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-slate-700 hover:bg-gray-50 transition-colors"
                                >
                                    Choisir un fichier
                                </label>

                                <span
                                    id="file-name"
                                    class="text-xs text-slate-muted truncate"
                                >
                                    {{ isset($domaine) && $domaine->visuel ? 'Remplacer le visuel' : 'Aucun fichier choisi' }}
                                </span>

                                <input
                                    id="visuel"
                                    type="file"
                                    name="visuel"
                                    accept=".jpg,.jpeg,.png,.webp,.svg"
                                    class="hidden"
                                >

                            </div>

                            <p class="text-[11px] text-slate-muted mt-1.5">
                                JPG, PNG, WebP ou SVG - 2 Mo maximum
                            </p>

                            @error('visuel')
                                <p class="text-red-500 text-xs mt-1">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </div>

                </div>

            </div>


            {{-- BOUTONS --}}
            <div class="flex gap-3">

                <button
                    type="submit"
                    class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                >

                    <i
                        data-lucide="{{ isset($domaine) ? 'save' : 'plus' }}"
                        class="w-4 h-4"
                    ></i>

                    {{ isset($domaine) ? 'Modifier le domaine' : 'Ajouter le domaine' }}

                </button>


                @if(isset($domaine))

                    <a
                        href="{{ route('domaines.index') }}"
                        class="px-5 py-3 rounded-xl border border-gray-200 text-slate-600 hover:bg-gray-50 font-medium text-sm flex items-center justify-center"
                    >
                        Annuler
                    </a>

                @endif

            </div>

        </form>

    </div>



    {{-- ========================================================= --}}
    {{-- COLONNE DROITE : LISTE                                    --}}
    {{-- ========================================================= --}}

    <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">

        <div class="flex items-center justify-between pb-3 border-b border-gray-100">

            <h3 class="font-bold text-slate-900 text-base">
                Domaines publiés ({{ $domaines->count() }})
            </h3>

        </div>


        {{-- LISTE --}}
        <div class="space-y-3 mt-4">

            @forelse($domaines as $item)

                <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:bg-gray-50/50 transition-all">

                    {{-- GAUCHE --}}
                    <div class="flex items-center gap-3 min-w-0">

                        {{-- IMAGE --}}
                        <div class="h-10 w-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-slate-400 shrink-0 overflow-hidden">

                            @if($item->visuel)

                                <img
                                    src="{{ asset('storage/' . $item->visuel) }}"
                                    alt="{{ $item->titre }}"
                                    class="w-full h-full object-cover"
                                >

                            @else

                                <i
                                    data-lucide="image"
                                    class="w-5 h-5"
                                ></i>

                            @endif

                        </div>


                        {{-- INFORMATIONS --}}
                        <div class="min-w-0">

                            <h4 class="font-bold text-sm text-slate-900 leading-tight truncate">
                                {{ $item->titre }}
                            </h4>

                            <p class="text-xs text-slate-muted mt-0.5 line-clamp-2">
                                {{ $item->description }}
                            </p>

                        </div>

                    </div>


                    {{-- ACTIONS --}}
                    <div class="flex items-center gap-1 ml-3 shrink-0">

                        {{-- MODIFIER --}}
                        <a
                            href="{{ route('domaines.edit', $item->id) }}"
                            title="Modifier"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                        >
                            <i
                                data-lucide="pencil"
                                class="w-4 h-4"
                            ></i>
                        </a>


                        {{-- SUPPRIMER --}}
                        <form
                            action="{{ route('domaines.destroy', $item->id) }}"
                            method="POST"
                            onsubmit="return confirm('Voulez-vous vraiment supprimer ce domaine ?');"
                        >

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                title="Supprimer"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
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

                {{-- AUCUN DOMAINE --}}
                <div class="text-center py-10">

                    <div class="h-12 w-12 mx-auto rounded-full bg-gray-50 flex items-center justify-center text-slate-400 mb-3">
                        <i
                            data-lucide="folder-open"
                            class="w-6 h-6"
                        ></i>
                    </div>

                    <p class="text-sm font-medium text-slate-700">
                        Aucun domaine
                    </p>

                    <p class="text-xs text-slate-muted mt-1">
                        Ajoutez votre premier domaine.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>


{{-- ============================================================= --}}
{{-- SCRIPT : NOM DU FICHIER                                       --}}
{{-- ============================================================= --}}

<script>

    const visuelInput = document.getElementById('visuel');
    const fileName = document.getElementById('file-name');

    if (visuelInput && fileName) {

        visuelInput.addEventListener('change', function () {

            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
            } else {
                fileName.textContent = 'Aucun fichier choisi';
            }

        });

    }

</script>

            </main>
        </div>
    </div>

    <!-- Script to render Lucide icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
