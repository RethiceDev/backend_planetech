<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Planetech Administration - Profil</title>

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

        <main class="p-8">

            {{-- ================================================= --}}
            {{-- TITRE --}}
            {{-- ================================================= --}}

            <div class="flex items-center justify-between mb-8">

                <div>
                    <div class="flex items-center gap-3">

                        <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="user-round" class="w-5 h-5"></i>
                        </div>

                        <div>
                            <h1 class="text-xl font-bold text-slate-900">
                                Mon profil
                            </h1>

                            <p class="text-sm text-slate-muted mt-0.5">
                                Consultez et modifiez vos informations administrateur.
                            </p>
                        </div>

                    </div>
                </div>

            </div>


            {{-- ================================================= --}}
            {{-- MESSAGES --}}
            {{-- ================================================= --}}

            @if(session('success'))

                <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">

                    <i data-lucide="circle-check" class="w-5 h-5 shrink-0"></i>

                    <span>
                        {{ session('success') }}
                    </span>

                </div>

            @endif


            @if(session('error'))

                <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">

                    <i data-lucide="circle-alert" class="w-5 h-5 shrink-0"></i>

                    <span>
                        {{ session('error') }}
                    </span>

                </div>

            @endif


            @if($errors->any())

                <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">

                    <div class="flex items-start gap-3">

                        <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>

                        <div>

                            <p class="font-semibold mb-1">
                                Veuillez corriger les erreurs :
                            </p>

                            <ul class="list-disc list-inside space-y-0.5">

                                @foreach($errors->all() as $error)

                                    <li>
                                        {{ $error }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    </div>

                </div>

            @endif


            {{-- ================================================= --}}
            {{-- CONTENU --}}
            {{-- ================================================= --}}

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">


                {{-- ================================================= --}}
                {{-- CARTE PROFIL --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-4">

                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                        {{-- En-tête profil --}}

                        <div class="bg-forest-900 px-6 py-8 text-center">

                            <div class="mx-auto h-24 w-24 rounded-full bg-white/10 border-4 border-white/20 flex items-center justify-center">

                                <span class="text-2xl font-bold text-white">

                                    {{ strtoupper(
                                        substr($admin->prenom, 0, 1) .
                                        substr($admin->nom, 0, 1)
                                    ) }}

                                </span>

                            </div>

                            <h2 class="mt-4 text-lg font-bold text-white">

                                {{ $admin->prenom }} {{ $admin->nom }}

                            </h2>

                            <p class="mt-1 text-sm text-emerald-200">

                                Administrateur

                            </p>

                        </div>


                        {{-- Informations --}}

                        <div class="p-6 space-y-5">

                            <div>

                                <p class="text-xs font-medium text-slate-muted uppercase tracking-wide">
                                    Nom complet
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                    {{ $admin->prenom }} {{ $admin->nom }}
                                </p>

                            </div>


                            <div>

                                <p class="text-xs font-medium text-slate-muted uppercase tracking-wide">
                                    Adresse email
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-900 break-all">
                                    {{ $admin->email }}
                                </p>

                            </div>


                            <div>

                                <p class="text-xs font-medium text-slate-muted uppercase tracking-wide">
                                    Fonction
                                </p>

                                <span class="inline-flex mt-1 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                    Administrateur
                                </span>

                            </div>


                            <div>

                                <p class="text-xs font-medium text-slate-muted uppercase tracking-wide">
                                    Compte créé le
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-900">

                                    {{ $admin->created_at
                                        ? $admin->created_at->format('d/m/Y')
                                        : 'Non disponible'
                                    }}

                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- FORMULAIRE --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-8 space-y-6">


                    {{-- INFORMATIONS PERSONNELLES --}}

                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">

                        <div class="flex items-center gap-3 mb-6">

                            <div class="h-9 w-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">

                                <i data-lucide="user-round" class="w-4 h-4"></i>

                            </div>

                            <div>

                                <h3 class="font-bold text-slate-900 text-base">
                                    Informations personnelles
                                </h3>

                                <p class="text-xs text-slate-muted mt-0.5">
                                    Modifiez vos informations de compte.
                                </p>

                            </div>

                        </div>


                        <form
                            action="{{ route('admin.profil.update') }}"
                            method="POST"
                            class="space-y-5"
                        >

                            @csrf
                            @method('PUT')


                            {{-- PRÉNOM --}}

                            <div>

                                <label
                                    for="prenom"
                                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                                >
                                    Prénom
                                </label>

                                <input
                                    type="text"
                                    id="prenom"
                                    name="prenom"
                                    value="{{ old('prenom', $admin->prenom) }}"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/10 bg-white"
                                >

                                @error('prenom')

                                    <p class="text-red-500 text-xs mt-1">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- NOM --}}

                            <div>

                                <label
                                    for="nom"
                                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                                >
                                    Nom
                                </label>

                                <input
                                    type="text"
                                    id="nom"
                                    name="nom"
                                    value="{{ old('nom', $admin->nom) }}"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/10 bg-white"
                                >

                                @error('nom')

                                    <p class="text-red-500 text-xs mt-1">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- EMAIL --}}

                            <div>

                                <label
                                    for="email"
                                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                                >
                                    Adresse email
                                </label>

                                <div class="relative">

                                    <i
                                        data-lucide="mail"
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                                    ></i>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="{{ old('email', $admin->email) }}"
                                        required
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-gray-200 text-sm text-slate-700 focus:outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/10 bg-white"
                                    >

                                </div>

                                @error('email')

                                    <p class="text-red-500 text-xs mt-1">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- BOUTON --}}

                            <div class="flex justify-end pt-2">

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm transition-colors shadow-sm"
                                >

                                    <i data-lucide="save" class="w-4 h-4"></i>

                                    Enregistrer les modifications

                                </button>

                            </div>

                        </form>

                    </div>


                    {{-- ================================================= --}}
                    {{-- MOT DE PASSE --}}
                    {{-- ================================================= --}}

                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">

                        <div class="flex items-center gap-3 mb-6">

                            <div class="h-9 w-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">

                                <i data-lucide="lock-keyhole" class="w-4 h-4"></i>

                            </div>

                            <div>

                                <h3 class="font-bold text-slate-900 text-base">
                                    Sécurité
                                </h3>

                                <p class="text-xs text-slate-muted mt-0.5">
                                    Modifiez votre mot de passe administrateur.
                                </p>

                            </div>

                        </div>


                        <form
                            action="{{ route('admin.profil.password') }}"
                            method="POST"
                            class="space-y-5"
                        >

                            @csrf
                            @method('PUT')


                            {{-- MOT DE PASSE ACTUEL --}}

                            <div>

                                <label
                                    for="current_password"
                                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                                >
                                    Mot de passe actuel
                                </label>

                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    required
                                    autocomplete="current-password"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/10"
                                >

                                @error('current_password')

                                    <p class="text-red-500 text-xs mt-1">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- NOUVEAU MOT DE PASSE --}}

                            <div>

                                <label
                                    for="password"
                                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                                >
                                    Nouveau mot de passe
                                </label>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/10"
                                >

                                @error('password')

                                    <p class="text-red-500 text-xs mt-1">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            {{-- CONFIRMATION --}}

                            <div>

                                <label
                                    for="password_confirmation"
                                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                                >
                                    Confirmer le nouveau mot de passe
                                </label>

                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/10"
                                >

                            </div>


                            <div class="flex justify-end pt-2">

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-medium text-sm transition-colors shadow-sm"
                                >

                                    <i data-lucide="key-round" class="w-4 h-4"></i>

                                    Modifier le mot de passe

                                </button>

                            </div>

                        </form>

                    </div>


                    {{-- ================================================= --}}
                    {{-- INFORMATIONS DE SÉCURITÉ --}}
                    {{-- ================================================= --}}

                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">

                        <div class="flex items-start gap-3">

                            <div class="h-9 w-9 rounded-lg bg-white text-blue-600 flex items-center justify-center shrink-0">

                                <i data-lucide="shield-check" class="w-5 h-5"></i>

                            </div>

                            <div>

                                <h4 class="font-semibold text-sm text-blue-900">
                                    Sécurité du compte
                                </h4>

                                <p class="text-xs text-blue-700 mt-1 leading-relaxed">
                                    Ne partagez jamais votre mot de passe.
                                    Utilisez un mot de passe suffisamment long et évitez
                                    d'utiliser le même mot de passe sur plusieurs services.
                                </p>

                            </div>

                        </div>

                    </div>

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
