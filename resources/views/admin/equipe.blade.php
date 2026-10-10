<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Planetech Administration - Équipe</title>

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

    {{-- SIDEBAR --}}
    @include('admin.aside')


    {{-- MAIN --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">


      


        {{-- PAGE CONTENT --}}
        <main class="p-8">


            {{-- TITRE --}}
            <div class="flex items-center gap-3 mb-8">

                <button class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-medium text-xs flex items-center gap-2 shadow-sm">

                    <i data-lucide="user-2" class="w-4 h-4"></i>

                    Équipes

                </button>

            </div>



            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">


                {{-- ================================================= --}}
                {{-- FORMULAIRE --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">

                    <div class="mb-6">

                        <h3 class="font-bold text-slate-900 text-base">

                            {{ isset($equipe) ? 'Modifier un membre' : 'Ajouter un membre' }}

                        </h3>

                        <p class="text-xs text-slate-muted mt-0.5">

                            Direction, pôle administratif ou technique.

                        </p>

                    </div>


                    {{-- MESSAGE SUCCÈS --}}
                    @if(session('success'))

                        <div class="mb-5 px-4 py-3 rounded-xl bg-green-100 border border-green-300 text-green-700 font-medium text-sm">

                            {{ session('success') }}

                        </div>

                    @endif


                    {{-- ERREURS --}}
                    @if($errors->any())

                        <div class="mb-5 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">

                            <p class="font-semibold mb-1">
                                Veuillez corriger les erreurs :
                            </p>

                            <ul class="list-disc list-inside">

                                @foreach($errors->all() as $error)

                                    <li>
                                        {{ $error }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    @endif



                    {{-- FORMULAIRE --}}
                    <form
                        action="{{ isset($equipe) ? route('equipes.update', $equipe->id) : route('equipes.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-5"
                    >

                        @csrf

                        @if(isset($equipe))
                            @method('PUT')
                        @endif



                        {{-- NOM --}}
                        <div>

                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">

                                Nom
                                <span class="text-red-500">*</span>

                            </label>

                            <input
                                type="text"
                                name="nom"
                                value="{{ old('nom', $equipe->nom ?? '') }}"
                                placeholder="Ex. Armel Assogba"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white"
                            >

                            @error('nom')

                                <p class="text-red-500 text-xs mt-1">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>



                        {{-- FONCTION --}}
                        <div>

                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">

                                Fonction
                                <span class="text-red-500">*</span>

                            </label>

                            <input
                                type="text"
                                name="role"
                                value="{{ old('role', $equipe->role ?? '') }}"
                                placeholder="Ex. Président-Directeur Général"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white"
                            >

                            @error('role')

                                <p class="text-red-500 text-xs mt-1">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>



                        {{-- POLE --}}
                        <div>

                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">

                                Pôle
                                <span class="text-red-500">*</span>

                            </label>


                            <div class="relative">

                                <select
                                    name="pole"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white appearance-none pr-10"
                                >

                                    <option value="">
                                        Sélectionner un pôle
                                    </option>

                                    <option
                                        value="Technique"
                                        {{ old('pole', $equipe->pole ?? '') == 'Technique' ? 'selected' : '' }}
                                    >
                                        Technique
                                    </option>

                                    <option
                                        value="Administratif"
                                        {{ old('pole', $equipe->pole ?? '') == 'Administratif' ? 'selected' : '' }}
                                    >
                                        Administratif
                                    </option>

                                    <option
                                        value="Direction"
                                        {{ old('pole', $equipe->pole ?? '') == 'Direction' ? 'selected' : '' }}
                                    >
                                        Direction
                                    </option>

                                </select>

                                <i
                                    data-lucide="chevron-down"
                                    class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                                ></i>

                            </div>


                            @error('pole')

                                <p class="text-red-500 text-xs mt-1">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>



                        {{-- PHOTO --}}
                        <div>

                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">

                                Photo

                            </label>


                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 bg-white">

                                <div class="flex items-center gap-4">


                                    {{-- APERCU --}}
                                    <div class="h-14 w-14 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center text-slate-400 shrink-0 overflow-hidden">

                                        @if(isset($equipe) && $equipe->photo)

                                            <img
                                                src="{{ asset('storage/' . $equipe->photo) }}"
                                                alt="{{ $equipe->nom }}"
                                                class="w-full h-full object-cover"
                                            >

                                        @else

                                            <i
                                                data-lucide="image"
                                                class="w-6 h-6"
                                            ></i>

                                        @endif

                                    </div>



                                    <div class="flex-1">

                                        <div class="flex items-center gap-3">

                                            <label
                                                for="photo"
                                                class="cursor-pointer px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-slate-700 hover:bg-gray-50 transition-colors"
                                            >

                                                Choisir un fichier

                                            </label>


                                            <span
                                                id="file-name"
                                                class="text-xs text-slate-muted truncate"
                                            >

                                                {{ isset($equipe) && $equipe->photo ? 'Remplacer la photo' : 'Aucun fichier choisi' }}

                                            </span>


                                            <input
                                                id="photo"
                                                type="file"
                                                name="photo"
                                                accept=".jpg,.jpeg,.png,.webp,.svg"
                                                class="hidden"
                                            >

                                        </div>


                                        <p class="text-[11px] text-slate-muted mt-1.5">

                                            JPG, PNG, WebP ou SVG - 2 Mo maximum

                                        </p>


                                        @error('photo')

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
                                    data-lucide="{{ isset($equipe) ? 'save' : 'plus' }}"
                                    class="w-4 h-4"
                                ></i>

                                {{ isset($equipe) ? 'Modifier le membre' : 'Ajouter le membre' }}

                            </button>


                            @if(isset($equipe))

                                <a
                                    href="{{ route('equipes.index') }}"
                                    class="px-5 py-3 rounded-xl border border-gray-200 text-slate-600 hover:bg-gray-50 font-medium text-sm flex items-center justify-center"
                                >

                                    Annuler

                                </a>

                            @endif

                        </div>

                    </form>

                </div>



                {{-- ================================================= --}}
                {{-- LISTE DES MEMBRES --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">


                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">

                        <h3 class="font-bold text-slate-900 text-base">

                            Équipe ({{ $equipes->count() }})

                        </h3>

                    </div>



                    <div class="space-y-3 mt-4">


                        @forelse($equipes as $membre)

                            <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition-all">


                                {{-- INFORMATIONS --}}
                                <div class="flex items-center gap-3 min-w-0">


                                    {{-- PHOTO --}}
                                    <div class="h-12 w-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-slate-400 shrink-0 overflow-hidden">

                                        @if($membre->photo)

                                            <img
                                                src="{{ asset('storage/' . $membre->photo) }}"
                                                alt="{{ $membre->nom }}"
                                                class="w-full h-full object-cover"
                                            >

                                        @else

                                            <i
                                                data-lucide="user"
                                                class="w-5 h-5"
                                            ></i>

                                        @endif

                                    </div>



                                    {{-- NOM + ROLE --}}
                                    <div class="min-w-0">

                                        <h4 class="font-bold text-sm text-slate-900 leading-tight truncate">

                                            {{ $membre->nom }}

                                        </h4>


                                        <p class="text-xs text-slate-muted mt-0.5">

                                            {{ $membre->role }}

                                        </p>


                                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-medium">

                                            {{ $membre->pole }}

                                        </span>

                                    </div>

                                </div>



                                {{-- ACTIONS --}}
                                <div class="flex items-center gap-1 ml-3 shrink-0">


                                    {{-- MODIFIER --}}
                                    <a
                                        href="{{ route('equipes.edit', $membre->id) }}"
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
                                        action="{{ route('equipes.destroy', $membre->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Voulez-vous vraiment supprimer ce membre ?');"
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


                            {{-- AUCUN MEMBRE --}}
                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-12 text-center flex flex-col items-center justify-center">

                                <div class="h-10 w-10 rounded-full bg-forest-50 text-forest-700 flex items-center justify-center mb-3">

                                    <i
                                        data-lucide="user"
                                        class="w-5 h-5"
                                    ></i>

                                </div>


                                <h4 class="font-bold text-slate-900 text-sm">

                                    Aucun membre

                                </h4>


                                <p class="text-xs text-slate-muted mt-1">

                                    Ajoutez les personnes à présenter sur la page d'accueil.

                                </p>

                            </div>


                        @endforelse

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>



{{-- NOM DU FICHIER --}}
<script>

    const photoInput = document.getElementById('photo');
    const fileName = document.getElementById('file-name');

    if (photoInput && fileName) {

        photoInput.addEventListener('change', function () {

            if (this.files.length > 0) {

                fileName.textContent = this.files[0].name;

            } else {

                fileName.textContent = 'Aucun fichier choisi';

            }

        });

    }

</script>



{{-- LUCIDE --}}
<script>

    lucide.createIcons();

</script>

</body>
</html>
