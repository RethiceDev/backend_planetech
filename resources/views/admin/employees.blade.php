<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Planetech Administration - Enregistrement des Employés</title>

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
                           text-green-700 font-medium text-sm">
                    {{ session('success') }}
                </div>

                @endif


                {{-- ========================================================= --}}
                {{-- MESSAGE ERREUR --}}
                {{-- ========================================================= --}}

                @if(session('error'))

                <div
                    class="px-4 py-3 rounded-xl bg-red-100 border border-red-300
                           text-red-700 font-medium text-sm">
                    {{ session('error') }}
                </div>

                @endif


                {{-- ========================================================= --}}
                {{-- ERREURS VALIDATION --}}
                {{-- ========================================================= --}}

                @if($errors->any())

                <div
                    class="px-4 py-3 rounded-xl bg-red-50 border border-red-200
                           text-red-700 text-sm">

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
                {{-- BOUTON ENREGISTREMENT --}}
                {{-- ========================================================= --}}

                <div class="flex items-center gap-3">

                    <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <i data-lucide="store" class="w-5 h-5"></i>
                    </div>

                    <div>

                        <h1 class="text-2xl font-bold text-slate-900">
                            Employés
                        </h1>

                        <p class="text-sm text-slate-muted mt-1">
                            Enregistrez un employer.
                        </p>

                    </div>
                </div>


                {{-- ========================================================= --}}
                {{-- GRILLE PRINCIPALE --}}
                {{-- ========================================================= --}}

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">


                    {{-- ===================================================== --}}
                    {{-- FORMULAIRE EMPLOYE --}}
                    {{-- ===================================================== --}}

                    <div
                        class="lg:col-span-7 bg-white rounded-2xl border border-gray-100
                           p-6 shadow-sm">

                        {{-- TITRE --}}

                        <div class="mb-6">

                            <h3 class="font-bold text-slate-900 text-base">

                                {{ isset($product)
                                ? 'Modifier un enregistrement'
                                : 'Enregistrer mon arrivée'
                            }}

                            </h3>

                            <p class="text-xs text-slate-muted mt-1">

                                {{ isset($product)
                                ? 'Modifiez les informations renseignées.'
                                : 'Votre présence sera ajoutée à la liste.'
                            }}

                            </p>

                        </div>


                        {{-- ================================================= --}}
                        {{-- FORMULAIRE --}}
                        {{-- ================================================= --}}

                        <form
                            action="{{ isset($employee)
        ? route('employees.update', $employee->id)
        : route('employees.store')
    }}"
                            method="POST"
                            enctype="multipart/form-data"
                            class="space-y-5">
                            @csrf

                            @if(isset($employee))
                            @method('PUT')
                            @endif


                            {{-- ================================================= --}}
                            {{-- INFORMATIONS DE L'EMPLOYE --}}
                            {{-- ================================================= --}}


                            {{-- Nom --}}

                            <div>
                                <label
                                    for="name"
                                    class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Nom <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $employee->name ?? '') }}"
                                    placeholder="Ex. DOSSOU"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 focus:outline-none
               focus:border-forest-700 bg-white"
                                    required>

                                @error('name')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- Prénoms --}}

                            <div>
                                <label for="prenoms" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Prénoms <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="prenoms"
                                    name="prenoms"
                                    value="{{ old('prenoms', $employee->prenoms ?? '') }}"
                                    placeholder="Ex. Jean Marc"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 focus:outline-none
               focus:border-forest-700 bg-white"
                                    required>
                                @error('prenoms')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- Email --}}

                            <div>
                                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email', $employee->email ?? '') }}"
                                    placeholder="Ex. jean.dossou@entreprise.com"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 focus:outline-none
               focus:border-forest-700 bg-white"
                                    required>
                                @error('email')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- Téléphone --}}
                            <div>
                                <label for="telephone" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Téléphone <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="tel"
                                    id="telephone"
                                    name="telephone"
                                    value="{{ old('telephone', $employee->telephone ?? '') }}"
                                    placeholder="Ex. 0197000000"
                                    inputmode="numeric"
                                    maxlength="10"
                                    minlength="10"
                                    pattern="[0-9]{10}"
                                    title="Le numéro doit contenir exactement 10 chiffres"
                                    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10)"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 focus:outline-none
               focus:border-forest-700 bg-white"
                                    required>
                                @error('telephone')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Service / département --}}

                            <div>
                                <label
                                    for="service" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Service / département

                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="service"
                                    name="service"
                                    value="{{ old('service', $employee->service ?? '') }}"
                                    placeholder="Ex. Comptabilité"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 focus:outline-none
               focus:border-forest-700 bg-white"
                                    required>

                                @error('service')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- Poste / fonction --}}

                            <div>
                                <label
                                    for="poste" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Poste / fonction
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="poste"
                                    name="poste"
                                    value="{{ old('poste', $employee->poste ?? '') }}"
                                    placeholder="Ex. Comptable"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 focus:outline-none
               focus:border-forest-700 bg-white"
                                    required>
                                @error('poste')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- Date d'embauche --}}

                            <div>
                                <label for="date_embauche" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Date d'embauche <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="date_embauche"
                                    name="date_embauche"
                                    value="{{ old('date_embauche', isset($employee) ? $employee->date_embauche->format('Y-m-d') : '') }}"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 focus:outline-none
               focus:border-forest-700 bg-white"
                                    required>
                                @error('date_embauche')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- Adresse MAC du téléphone --}}
                            <div>
                                <label for="mac_address" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Adresse MAC de votre téléphone <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="mac_address"
                                    name="mac_address"
                                    value="{{ old('mac_address', $employee->mac_address ?? '') }}"
                                    placeholder="Ex. A4:B1:C2:D3:E4:F5"
                                    pattern="^([0-9A-Fa-f]{2}[:\-]){5}[0-9A-Fa-f]{2}$"
                                    maxlength="17"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 font-mono uppercase focus:outline-none
               focus:border-forest-700 bg-white"
                                    required>
                                @error('mac_address')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- ============ FACULTATIFS ============ --}}

                            {{-- Adresse MAC 2e appareil --}}
                            <div>
                                <label for="mac_address_2" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Adresse MAC d'un 2e appareil
                                </label>
                                <input
                                    type="text"
                                    id="mac_address_2"
                                    name="mac_address_2"
                                    value="{{ old('mac_address_2', $employee->mac_address_2 ?? '') }}"
                                    placeholder="Ex. A4:B1:C2:D3:E4:F6"
                                    pattern="^([0-9A-Fa-f]{2}[:\-]){5}[0-9A-Fa-f]{2}$"
                                    maxlength="17"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 font-mono uppercase focus:outline-none
               focus:border-forest-700 bg-white">
                                @error('mac_address_2')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- Photo --}}
                            <div>
                                <label for="photo" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Photo
                                </label>
                                <input
                                    type="file"
                                    id="photo"
                                    name="photo"
                                    accept="image/png,image/jpeg,image/webp"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 bg-white
               file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
               file:text-xs file:font-semibold file:bg-gray-100 file:text-slate-700">
                                @error('photo')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
                            </div>

                            {{-- Type de contrat --}}
                            <div>
                                <label for="type_contrat" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Type de contrat
                                </label>

                                <select
                                    id="type_contrat"
                                    name="type_contrat"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200
               text-sm text-slate-700 focus:outline-none
               focus:border-forest-700 bg-white">
                                    <option value="">— Choisir —</option>
                                    @foreach (['CDI', 'CDD', 'Stage', 'Prestataire'] as $type)
                                    <option value="{{ $type }}" @selected(old('type_contrat', $employee->type_contrat ?? '') === $type)>
                                        {{ $type }}
                                    </option>
                                    @endforeach
                                </select>

                                @error('type_contrat')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>

                                @enderror
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
                                           justify-center hover:bg-gray-50">
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
                                justify-center gap-2 shadow-sm">

                                    <i
                                        data-lucide="{{ isset($product)
                                        ? 'save'
                                        : 'plus'
                                    }}"
                                        class="w-4 h-4"></i>


                                    {{ isset($product)
                                    ? 'Modifier les informations'
                                    : 'Enregistrer'
                                }}

                                </button>

                            </div>

                        </form>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- LISTE DES EMPLOYÉS --}}
                    {{-- ===================================================== --}}

                    <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">

                        {{-- EN-TÊTE --}}
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-slate-900 text-base">
                                Employés ({{ $employees->count() }})
                            </h3>
                        </div>

                        {{-- RECHERCHE --}}
                        <input
                            id="searchInput"
                            type="text"
                            placeholder="Rechercher par nom, service ou poste"
                            class="w-full px-4 py-2.5 mb-4
               rounded-xl
               border border-gray-200
               text-xs outline-none
               focus:border-emerald-600
               focus:ring-2
               focus:ring-emerald-100">

                        {{-- LISTE SCROLLABLE --}}
                        <div id="employeesList" class="max-h-[calc(100vh-250px)] overflow-y-auto pr-2 space-y-3">

                            @forelse($employees as $employeeItem)

                            <div
                                class="employee-item border border-gray-100 rounded-xl p-4 hover:shadow-sm transition"
                                data-search="{{ strtolower(
                    $employeeItem->name . ' ' .
                    $employeeItem->prenoms . ' ' .
                    $employeeItem->service . ' ' .
                    $employeeItem->poste
                ) }}">

                                <div class="flex items-center gap-3">

                                    {{-- PHOTO --}}
                                    <div
                                        class="h-16 w-16 rounded-xl bg-gray-50 border border-gray-100
                               flex items-center justify-center overflow-hidden shrink-0">
                                        @if($employeeItem->photo)
                                        <img
                                            src="{{ asset('storage/' . $employeeItem->photo) }}"
                                            alt="{{ $employeeItem->name }}"
                                            class="w-full h-full object-cover">
                                        @else
                                        <span class="text-sm font-bold text-forest-700">
                                            {{ strtoupper(
                                    mb_substr($employeeItem->name, 0, 1) .
                                    mb_substr($employeeItem->prenoms, 0, 1)
                                ) }}
                                        </span>
                                        @endif
                                    </div>

                                    {{-- INFORMATIONS --}}
                                    <div class="flex-1 min-w-0">

                                        <h4 class="font-bold text-slate-900 text-sm truncate">
                                            {{ $employeeItem->name }} {{ $employeeItem->prenoms }}
                                        </h4>

                                        <p class="text-xs text-emerald-700 font-medium truncate">
                                            {{ $employeeItem->service }} — {{ $employeeItem->poste }}
                                        </p>

                                        <p class="text-[11px] text-slate-muted mt-0.5 font-mono">
                                            {{ $employeeItem->mac_address }}
                                        </p>

                                        @if($employeeItem->type_contrat)
                                        <span
                                            class="inline-flex mt-1 px-2 py-0.5 rounded-full
                                       bg-green-100 text-green-700 text-[10px] font-medium">
                                            {{ $employeeItem->type_contrat }}
                                        </span>
                                        @endif

                                    </div>

                                    {{-- ACTIONS --}}
                                    <div class="flex items-center gap-1 shrink-0">

                                        {{-- MODIFIER --}}
                                        <a
                                            href="{{ route('employees.edit', $employeeItem->id) }}"
                                            class="p-2 rounded-lg text-slate-500 hover:bg-gray-100 hover:text-emerald-700"
                                            title="Modifier">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>

                                        {{-- SUPPRIMER --}}
                                        <form
                                            action="{{ route('employees.destroy', $employeeItem->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Voulez-vous vraiment supprimer cet employé ?');">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="p-2 rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600"
                                                title="Supprimer">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>

                                    </div>

                                </div>

                            </div>

                            @empty

                            {{-- AUCUN EMREGISTREMENT --}}
                            <div
                                class="border-2 border-dashed border-gray-200 rounded-2xl p-12
                       text-center flex flex-col items-center justify-center">
                                <div
                                    class="h-10 w-10 rounded-full bg-forest-50 text-forest-700
                           flex items-center justify-center mb-3">
                                    <i data-lucide="users" class="w-5 h-5"></i>
                                </div>

                                <h4 class="font-bold text-slate-900 text-sm">
                                    Aucun enregistrement
                                </h4>

                                <p class="text-xs text-slate-muted mt-1">
                                    Enregistrez un Enpmoyé
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
        // APERÇU PHOTO
        // =============================================================

        const photoInput = document.getElementById('photo');
        const fileName = document.getElementById('file-name');
        const photoPreview = document.getElementById('photo-preview');


        if (photoInput) {

            photoInput.addEventListener('change', function() {

                if (this.files && this.files.length > 0) {

                    const file = this.files[0];

                    fileName.textContent = file.name;


                    const reader = new FileReader();


                    reader.onload = function(event) {

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

        // RECHERCHE EMPLOYÉS
        const searchInput = document.getElementById('searchInput');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();

                document.querySelectorAll('.employee-item').forEach(function(item) {
                    item.style.display = item.dataset.search.includes(term) ? '' : 'none';
                });
            });
        }
    </script>


</body>

</html>
