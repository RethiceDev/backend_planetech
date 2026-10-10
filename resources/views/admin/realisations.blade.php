<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Planetech Administration - Réalisations</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            50: '#f2f7f4',
                            700: '#1b4d3e',
                            800: '#153f32',
                            900: '#0f2c23'
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

    {{-- CONTENU --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        <main class="p-8 space-y-6">

            {{-- =====================================================
                 MESSAGES
            ====================================================== --}}

            @if(session('success'))
                <div
                    class="rounded-xl border border-green-300 bg-green-100 px-4 py-3 text-sm font-medium text-green-700"
                >
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div
                    class="rounded-xl border border-red-300 bg-red-100 px-4 py-3 text-sm font-medium text-red-700"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- =====================================================
                 EN-TÊTE
            ====================================================== --}}

            <div class="flex items-center gap-3">

                 <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="image" class="w-5 h-5"></i>
                        </div>

                    <div>


                    <h1 class="mt-1 text-2xl font-bold text-slate-900">
                        Réalisations
                    </h1>

                    <p class="mt-1 text-sm text-slate-muted">
                        Ajoutez et gérez les photos des réalisations de PLANETECH SARL.
                    </p>
                </div>

                <div>
                    <span
                        class="inline-flex items-center gap-2 rounded-xl bg-forest-50 px-4 py-2 text-xs font-semibold text-forest-700"
                    >
                        <i data-lucide="images" class="h-4 w-4"></i>
                        {{ $realisations->count() }} réalisation(s)
                    </span>
                </div>
            </div>

            {{-- =====================================================
                 GRILLE
            ====================================================== --}}

            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">

                {{-- =================================================
                     FORMULAIRE
                ================================================== --}}

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-5">

                    <div class="mb-6">
                        <h2 class="text-base font-bold text-slate-900">
                            {{ isset($realisation)
                                ? 'Modifier la réalisation'
                                : 'Ajouter une réalisation'
                            }}
                        </h2>

                        <p class="mt-1 text-xs text-slate-muted">
                            {{ isset($realisation)
                                ? 'Modifiez les informations de cette réalisation.'
                                : 'La réalisation apparaîtra sur la page publique.'
                            }}
                        </p>
                    </div>

                    <form
                        action="{{ isset($realisation)
                            ? route('realisations.update', $realisation->id)
                            : route('realisations.store')
                        }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-5"
                    >

                        @csrf

                        @if(isset($realisation))
                            @method('PUT')
                        @endif

                        {{-- DOMAINE --}}
<div>
    <label
        for="domaine"
        class="mb-1.5 block text-xs font-semibold text-slate-700"
    >
        Domaine
        <span class="text-red-500">*</span>
    </label>

    <select
        id="domaine"
        name="domaine"
        class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 focus:border-forest-700 focus:outline-none"
        required
    >
        <option value="">
            Sélectionner un domaine
        </option>

        <option
            value="Électricité"
            {{ old('domaine', $realisation->domaine ?? '') === 'Électricité' ? 'selected' : '' }}
        >
            Électricité
        </option>

        <option
            value="BTP"
            {{ old('domaine', $realisation->domaine ?? '') === 'BTP' ? 'selected' : '' }}
        >
            BTP
        </option>

        <option
            value="Climatisation"
            {{ old('domaine', $realisation->domaine ?? '') === 'Climatisation' ? 'selected' : '' }}
        >
            Climatisation
        </option>

        <option
            value="Branchement FTTH"
            {{ old('domaine', $realisation->domaine ?? '') === 'Branchement FTTH' ? 'selected' : '' }}
        >
            Branchement FTTH
        </option>

        <option
            value="Fibre optique"
            {{ old('domaine', $realisation->domaine ?? '') === 'Fibre optique' ? 'selected' : '' }}
        >
            Fibre optique
        </option>

        <option
            value="Réseau informatique"
            {{ old('domaine', $realisation->domaine ?? '') === 'Réseau informatique' ? 'selected' : '' }}
        >
            Réseau informatique
        </option>

        <option
            value="Sécurité & incendie"
            {{ old('domaine', $realisation->domaine ?? '') === 'Sécurité & incendie' ? 'selected' : '' }}
        >
            Sécurité & incendie
        </option>

        <option
            value="Informatique"
            {{ old('domaine', $realisation->domaine ?? '') === 'Informatique' ? 'selected' : '' }}
        >
            Informatique
        </option>

        <option
            value="Télécom"
            {{ old('domaine', $realisation->domaine ?? '') === 'Télécom' ? 'selected' : '' }}
        >
            Télécom
        </option>
    </select>

    @error('domaine')
        <p class="mt-1 text-xs text-red-500">
            {{ $message }}
        </p>
    @enderror
</div>

                        {{-- TITRE --}}
                        <div>
                            <label
                                for="titre"
                                class="mb-1.5 block text-xs font-semibold text-slate-700"
                            >
                                Titre
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                id="titre"
                                name="titre"
                                value="{{ old('titre', $realisation->titre ?? '') }}"
                                placeholder="Ex. Installation électrique professionnelle"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 focus:border-forest-700 focus:outline-none"
                                required
                            >
                        </div>

                        {{-- DESCRIPTION --}}
                        <div>
                            <label
                                for="description"
                                class="mb-1.5 block text-xs font-semibold text-slate-700"
                            >
                                Description
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Décrivez brièvement la réalisation..."
                                class="w-full resize-none rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 focus:border-forest-700 focus:outline-none"
                                required
                            >{{ old('description', $realisation->description ?? '') }}</textarea>
                        </div>

                        {{-- IMAGE --}}
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-semibold text-slate-700"
                            >
                                Photo
                                @if(!isset($realisation))
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>

                            <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white p-4">

                                <div class="flex items-center gap-4">

                                    {{-- APERÇU --}}
                                    <div
                                        id="image-preview"
                                        class="flex h-24 w-28 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-gray-50 text-slate-400"
                                    >
                                        @if(isset($realisation) && $realisation->image)
                                            <img
                                                src="{{ asset('storage/' . $realisation->image) }}"
                                                alt="{{ $realisation->titre }}"
                                                class="h-full w-full object-cover"
                                            >
                                        @else
                                            <i
                                                data-lucide="image"
                                                class="h-7 w-7"
                                            ></i>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">

                                        <div class="flex items-center gap-3">
                                            <label
                                                for="image"
                                                class="cursor-pointer rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-gray-50"
                                            >
                                                Choisir une photo
                                            </label>

                                            <span
                                                id="file-name"
                                                class="truncate text-xs text-slate-muted"
                                            >
                                                Aucun fichier choisi
                                            </span>
                                        </div>

                                        <input
                                            type="file"
                                            id="image"
                                            name="image"
                                            accept=".jpg,.jpeg,.png,.webp"
                                            class="hidden"
                                            {{ !isset($realisation) ? 'required' : '' }}
                                        >

                                        <p class="mt-1.5 text-[11px] text-slate-muted">
                                            JPG, PNG ou WebP — 5 Mo maximum
                                        </p>

                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ORDRE --}}
                        <div>
                            <label
                                for="ordre"
                                class="mb-1.5 block text-xs font-semibold text-slate-700"
                            >
                                Ordre d'affichage
                            </label>

                            <input
                                type="number"
                                id="ordre"
                                name="ordre"
                                value="{{ old('ordre', $realisation->ordre ?? 0) }}"
                                min="0"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 focus:border-forest-700 focus:outline-none"
                            >

                            <p class="mt-1 text-[11px] text-slate-muted">
                                Plus le nombre est petit, plus la réalisation apparaît tôt.
                            </p>
                        </div>

                        {{-- ACTIF --}}
                        <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/50 p-4">

                            <input
                                type="checkbox"
                                id="actif"
                                name="actif"
                                value="1"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                {{ old(
                                    'actif',
                                    $realisation->actif ?? true
                                ) ? 'checked' : '' }}
                            >

                            <div>
                                <label
                                    for="actif"
                                    class="block text-xs font-semibold text-slate-900"
                                >
                                    Visible sur le site
                                </label>

                                <p class="mt-1 text-[11px] text-slate-muted">
                                    Décochez pour masquer la réalisation sans la supprimer.
                                </p>
                            </div>
                        </div>

                        {{-- BOUTONS --}}
                        <div class="flex gap-3">

                            @if(isset($realisation))
                                <a
                                    href="{{ route('realisations.index') }}"
                                    class="flex-1 rounded-xl border border-gray-200 py-3 text-center text-sm font-medium text-slate-700 hover:bg-gray-50"
                                >
                                    Annuler
                                </a>
                            @endif

                            <button
                                type="submit"
                                class="{{ isset($realisation) ? 'flex-1' : 'w-full' }} flex items-center justify-center gap-2 rounded-xl bg-emerald-600 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:bg-emerald-700"
                            >
                                <i
                                    data-lucide="{{ isset($realisation) ? 'save' : 'plus' }}"
                                    class="h-4 w-4"
                                ></i>

                                {{ isset($realisation)
                                    ? 'Modifier la réalisation'
                                    : 'Ajouter la réalisation'
                                }}
                            </button>

                        </div>

                    </form>
                </div>

                {{-- =================================================
                     LISTE
                ================================================== --}}

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-7">



                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                Réalisations
                            </h2>

                            <p class="mt-1 text-xs text-slate-muted">
                                Photos actuellement enregistrées.
                            </p>
                        </div>
                    </div>

                    <div class="max-h-[calc(100vh-250px)] space-y-3 overflow-y-auto pr-2">

                        @forelse($realisations as $item)

                            <div class="rounded-xl border border-gray-100 p-4 transition hover:shadow-sm">

                                <div class="flex items-center gap-4">

                                    {{-- PHOTO --}}
                                    <div class="h-24 w-28 shrink-0 overflow-hidden rounded-xl border border-gray-100 bg-gray-50">

                                        @if($item->image)
                                            <img
                                                src="{{ asset('storage/' . $item->image) }}"
                                                alt="{{ $item->titre }}"
                                                class="h-full w-full object-cover"
                                            >
                                        @else
                                            <div class="flex h-full items-center justify-center text-slate-400">
                                                <i
                                                    data-lucide="image-off"
                                                    class="h-6 w-6"
                                                ></i>
                                            </div>
                                        @endif

                                    </div>

                                    {{-- INFORMATIONS --}}
                                    <div class="min-w-0 flex-1">

                                        <div class="mb-1 flex flex-wrap items-center gap-2">
                                            <h3 class="truncate text-sm font-bold text-slate-900">
                                                {{ $item->titre }}
                                            </h3>

                                            @if($item->actif)
                                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700">
                                                    Visible
                                                </span>
                                            @else
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">
                                                    Masquée
                                                </span>
                                            @endif
                                        </div>

                                        <span class="inline-flex rounded-full bg-forest-50 px-2.5 py-1 text-[10px] font-semibold text-forest-700">
                                            {{ $item->domaine }}
                                        </span>

                                        <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-muted">
                                            {{ $item->description }}
                                        </p>

                                    </div>

                                    {{-- ACTIONS --}}
                                    <div class="flex shrink-0 items-center gap-1">

                                        <a
                                            href="{{ route('realisations.edit', $item->id) }}"
                                            class="rounded-lg p-2 text-slate-500 hover:bg-gray-100 hover:text-emerald-700"
                                            title="Modifier"
                                        >
                                            <i
                                                data-lucide="pencil"
                                                class="h-4 w-4"
                                            ></i>
                                        </a>

                                        <form
                                            action="{{ route('realisations.destroy', $item->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Voulez-vous vraiment supprimer cette réalisation ?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="rounded-lg p-2 text-slate-500 hover:bg-red-50 hover:text-red-600"
                                                title="Supprimer"
                                            >
                                                <i
                                                    data-lucide="trash-2"
                                                    class="h-4 w-4"
                                                ></i>
                                            </button>
                                        </form>

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">

                                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-forest-50 text-forest-700">
                                    <i
                                        data-lucide="images"
                                        class="h-6 w-6"
                                    ></i>
                                </div>

                                <h3 class="text-sm font-bold text-slate-900">
                                    Aucune réalisation
                                </h3>

                                <p class="mt-1 text-xs text-slate-muted">
                                    Ajoutez la première photo de réalisation.
                                </p>

                            </div>

                        @endforelse

                    </div>
                </div>

            </div>

        </main>

    </div>

</div>

<script>
    lucide.createIcons();

    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    const fileName = document.getElementById('file-name');

    if (imageInput) {
        imageInput.addEventListener('change', function () {

            if (!this.files || !this.files.length) {
                fileName.textContent = 'Aucun fichier choisi';
                return;
            }

            const file = this.files[0];

            fileName.textContent = file.name;

            const reader = new FileReader();

            reader.onload = function (event) {
                imagePreview.innerHTML = `
                    <img
                        src="${event.target.result}"
                        alt="Aperçu"
                        class="h-full w-full object-cover"
                    >
                `;
            };

            reader.readAsDataURL(file);
        });
    }
</script>

</body>
</html>
