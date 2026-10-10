<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Planetech Administration - Partenaires</title>

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

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        

        <!-- PAGE CONTENT -->
        <main class="p-8 space-y-6">

            <!-- MESSAGE DE SUCCÈS -->
            @if(session('success'))
                <div class="px-4 py-3 rounded-xl bg-green-100 border border-green-300 text-green-700 font-medium text-sm">
                    {{ session('success') }}
                </div>
            @endif


            <!-- ERREURS -->
            @if($errors->any())
                <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">

                    <ul class="list-disc list-inside space-y-1">

                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>
            @endif


            <!-- NAVIGATION -->
            <div class="flex items-center gap-3">

                <a
                    href="{{ route('partenaires.index') }}"
                    class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-medium text-xs flex items-center gap-2 shadow-sm"
                >
                    <i data-lucide="building-2" class="w-4 h-4"></i>

                    Partenaires
                </a>

            </div>


            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- ============================= -->
                <!-- FORMULAIRE -->
                <!-- ============================= -->

                <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">

                    <div>

                        <h3 class="font-bold text-slate-900 text-base">

                            {{ isset($partenaire)
                                ? 'Modifier le partenaire'
                                : 'Ajouter un partenaire'
                            }}

                        </h3>

                        <p class="text-xs text-slate-muted mt-0.5">

                            {{ isset($partenaire)
                                ? 'Modifiez les informations du partenaire.'
                                : 'Le logo s’affiche dans le bandeau partenaires.'
                            }}

                        </p>

                    </div>


                    <!-- FORM -->
                    <form
                        action="{{ isset($partenaire)
                            ? route('partenaires.update', $partenaire->id)
                            : route('partenaires.store')
                        }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-5"
                    >

                        @csrf

                        @if(isset($partenaire))
                            @method('PUT')
                        @endif


                        <!-- NOM -->
                        <div>

                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Nom
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="nom"
                                value="{{ old('nom', $partenaire->nom ?? '') }}"
                                placeholder="Ex. Fournisseur réseau"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white"
                            >

                            @error('nom')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <!-- SITE WEB -->
                        <div>

                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Site web
                            </label>

                            <input
                                type="url"
                                name="site_web"
                                value="{{ old('site_web', $partenaire->site_web ?? '') }}"
                                placeholder="https://exemple.bj"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 bg-white"
                            >

                            <p class="text-[11px] text-slate-muted mt-1">
                                Optionnel. Le logo devient cliquable.
                            </p>

                            @error('site_web')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <!-- LOGO -->
                        <div>

                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Logo
                            </label>

                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 flex items-center gap-4 bg-white">

                                <!-- APERÇU -->
                                <div
                                    id="logo-preview"
                                    class="h-14 w-14 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center text-slate-400 shrink-0 overflow-hidden"
                                >

                                    @if(isset($partenaire) && $partenaire->logo)

                                        <img
                                            src="{{ asset('storage/' . $partenaire->logo) }}"
                                            alt="{{ $partenaire->nom }}"
                                            class="w-full h-full object-contain"
                                        >

                                    @else

                                        <i data-lucide="image" class="w-6 h-6"></i>

                                    @endif

                                </div>


                                <div class="flex-1">

                                    <div class="flex items-center gap-3">

                                        <label
                                            for="logo"
                                            class="cursor-pointer px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-slate-700 hover:bg-gray-50 transition-colors"
                                        >
                                            Choisir un fichier
                                        </label>

                                        <span
                                            id="file-name"
                                            class="text-xs text-slate-muted"
                                        >
                                            Aucun fichier choisi
                                        </span>

                                    </div>

                                    <p class="text-[11px] text-slate-muted mt-1.5">
                                        JPG, PNG, WebP ou SVG - 2 Mo maximum
                                    </p>

                                    <input
                                        id="logo"
                                        type="file"
                                        name="logo"
                                        accept=".jpg,.jpeg,.png,.webp,.svg"
                                        class="hidden"
                                    >

                                    @error('logo')
                                        <p class="text-xs text-red-500 mt-1">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>

                            </div>

                        </div>


                        <!-- BOUTONS -->
                        <div class="flex gap-3">

                            @if(isset($partenaire))

                                <a
                                    href="{{ route('partenaires.index') }}"
                                    class="flex-1 py-3 rounded-xl border border-gray-200 text-slate-700 font-medium text-sm transition-colors flex items-center justify-center gap-2 hover:bg-gray-50"
                                >
                                    Annuler
                                </a>

                            @endif


                            <button
                                type="submit"
                                class="{{ isset($partenaire) ? 'flex-1' : 'w-full' }} py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                            >

                                <i
                                    data-lucide="{{ isset($partenaire) ? 'save' : 'plus' }}"
                                    class="w-4 h-4"
                                ></i>

                                {{ isset($partenaire)
                                    ? 'Modifier le partenaire'
                                    : 'Ajouter le partenaire'
                                }}

                            </button>

                        </div>

                    </form>

                </div>


                <!-- ============================= -->
                <!-- LISTE DES PARTENAIRES -->
                <!-- ============================= -->

                <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-6">

                    <div class="flex items-center justify-between">

                        <h3 class="font-bold text-slate-900 text-base">

                            Partenaires ({{ $partenaires->count() }})

                        </h3>

                    </div>


                    @forelse($partenaires as $partenaireItem)

                        <div class="border border-gray-100 rounded-2xl p-4 hover:shadow-sm transition">

                            <div class="flex items-center gap-4">

                                <!-- LOGO -->
                                <div class="h-16 w-16 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden shrink-0">

                                    @if($partenaireItem->logo)

                                        <img
                                            src="{{ asset('storage/' . $partenaireItem->logo) }}"
                                            alt="{{ $partenaireItem->nom }}"
                                            class="w-full h-full object-contain p-2"
                                        >

                                    @else

                                        <i
                                            data-lucide="building-2"
                                            class="w-6 h-6 text-slate-400"
                                        ></i>

                                    @endif

                                </div>


                                <!-- INFORMATIONS -->
                                <div class="flex-1 min-w-0">

                                    <h4 class="font-bold text-slate-900 text-sm">
                                        {{ $partenaireItem->nom }}
                                    </h4>

                                    @if($partenaireItem->site_web)

                                        <a
                                            href="{{ $partenaireItem->site_web }}"
                                            target="_blank"
                                            class="text-xs text-emerald-700 hover:underline break-all"
                                        >
                                            {{ $partenaireItem->site_web }}
                                        </a>

                                    @else

                                        <p class="text-xs text-slate-muted">
                                            Aucun site web
                                        </p>

                                    @endif

                                </div>


                                <!-- ACTIONS -->
                                <div class="flex items-center gap-1">

                                    <!-- MODIFIER -->
                                    <a
                                        href="{{ route('partenaires.edit', $partenaireItem->id) }}"
                                        class="p-2 rounded-lg text-slate-500 hover:bg-gray-100 hover:text-emerald-700"
                                        title="Modifier"
                                    >
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>


                                    <!-- SUPPRIMER -->
                                    <form
                                        action="{{ route('partenaires.destroy', $partenaireItem->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Voulez-vous vraiment supprimer ce partenaire ?');"
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

                        <!-- EMPTY STATE -->
                        <div class="border-2 border-dashed border-gray-200 rounded-2xl p-12 text-center flex flex-col items-center justify-center">

                            <div class="h-10 w-10 rounded-full bg-forest-50 text-forest-700 flex items-center justify-center mb-3">

                                <i data-lucide="building-2" class="w-5 h-5"></i>

                            </div>

                            <h4 class="font-bold text-slate-900 text-sm">
                                Aucun partenaire
                            </h4>

                            <p class="text-xs text-slate-muted mt-1">
                                Ajoutez les marques et opérateurs avec lesquels vous travaillez.
                            </p>

                        </div>

                    @endforelse

                </div>

            </div>

        </main>

    </div>

</div>


<!-- SCRIPT -->
<script>

    // Lucide
    lucide.createIcons();


    // Sélection du logo
    const logoInput = document.getElementById('logo');
    const fileName = document.getElementById('file-name');
    const logoPreview = document.getElementById('logo-preview');

    if (logoInput) {

        logoInput.addEventListener('change', function () {

            if (this.files && this.files.length > 0) {

                const file = this.files[0];

                fileName.textContent = file.name;

                // Prévisualisation
                const reader = new FileReader();

                reader.onload = function (e) {

                    logoPreview.innerHTML = `
                        <img
                            src="${e.target.result}"
                            class="w-full h-full object-contain p-1"
                            alt="Aperçu du logo"
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
