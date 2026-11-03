<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planetech Administration - Procurations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { forest: { 50: '#f2f7f4', 700: '#1b4d3e', 900: '#0f2c23' } }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">
    @include('admin.aside')

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <main class="p-8 space-y-6">

            @if(session('success'))
                <div class="px-4 py-3 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 text-sm flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="px-4 py-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-800 text-sm flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i> {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-2xl bg-forest-700 text-white flex items-center justify-center shadow-lg">
                    <i data-lucide="file-text" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Gestion des Procurations</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Établissez et gérez vos procurations professionnelles.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- FORMULAIRE --}}
                <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900 mb-5 flex items-center gap-2 border-b pb-3">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-forest-700"></i> Nouvelle Procuration
                    </h2>

                    <form action="{{ route('admin.procurations.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Nom & Prénom du Procurant *</label>
                                <input type="text" name="nom_procurant" required value="{{ old('nom_procurant') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Rôle / Poste du Procurant *</label>
                                <input type="text" name="role_procurant" placeholder="ex: Stagiaire assistant comptable" required value="{{ old('role_procurant') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                           <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Numéro CIP (10 chiffres) *</label>
                                <input type="text"
                                    name="cip_procurant"
                                    maxlength="10"
                                    pattern="[0-9]{10}"
                                    inputmode="numeric"
                                    placeholder="ex: 2950708499"
                                    required
                                    value="{{ old('cip_procurant') }}"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Date d'expiration de la carte CIP *</label>
                                <input type="date" name="expiration_cip" required value="{{ old('expiration_cip') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Adresse E-mail du Procurant</label>
                                <input type="email" name="email_procurant" placeholder="E-mail (facultatif)" value="{{ old('email_procurant') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Valide du (Début) *</label>
                                <input type="date" name="date_debut" required value="{{ old('date_debut') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Valide au (Fin) *</label>
                                <input type="date" name="date_fin" required value="{{ old('date_fin') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                        </div>

                        {{-- LISTE DYNAMIQUE DES ACTIONS (BOUTON +) --}}
                        <div class="pt-2 border-t space-y-3">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Actions autorisées</label>
                                <button type="button" id="add-action-btn" class="px-3 py-1.5 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-lg flex items-center gap-1">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Ajouter une action
                                </button>
                            </div>

                            <div id="actions-container" class="space-y-2">
                                <div class="action-row flex gap-2 items-center">
                                    <input type="text" name="actions[]" placeholder="ex: Retrait de CHEQUE" required class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 bg-forest-700 hover:bg-forest-900 text-white font-medium rounded-xl shadow-md transition-all flex items-center justify-center gap-2 mt-4">
                            <i data-lucide="send" class="w-4 h-4"></i> Générer et envoyer par e-mail
                        </button>
                    </form>
                </div>

                {{-- HISTORIQUE & TÉLÉCHARGEMENT --}}
              <div class="lg:col-span-5 min-w-0 bg-white rounded-2xl border border-gray-100 p-5 lg:p-6 shadow-sm space-y-4">

    {{-- EN-TÊTE --}}
    <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 shrink-0 rounded-xl bg-emerald-50 flex items-center justify-center">
                <i data-lucide="history" class="w-5 h-5 text-forest-700"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-slate-900">Historique des procurations</h2>
                <p class="text-xs text-slate-500 mt-0.5">Consultez, imprimez ou téléchargez les procurations.</p>
            </div>
        </div>

        <span class="shrink-0 inline-flex items-center rounded-full bg-forest-50 text-forest-700 text-xs font-bold px-3 py-1">
            {{ $procurations->total() }}
        </span>
    </div>

    <div class="space-y-3">

        @forelse($procurations as $p)

            @php
                $debut = \Carbon\Carbon::parse($p->date_debut)->startOfDay();
                $fin   = \Carbon\Carbon::parse($p->date_fin)->endOfDay();

                if (now()->lt($debut)) {
                    $etat = ['label' => 'À venir', 'icon' => 'clock',        'bar' => 'border-l-blue-400',    'badge' => 'bg-blue-50 text-blue-700'];
                } elseif (now()->gt($fin)) {
                    $etat = ['label' => 'Expirée', 'icon' => 'calendar-x',   'bar' => 'border-l-red-400',     'badge' => 'bg-red-50 text-red-700'];
                } else {
                    $etat = ['label' => 'En cours', 'icon' => 'check-circle', 'bar' => 'border-l-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700'];
                }
            @endphp

            <article class="group p-4 rounded-xl border border-slate-100 border-l-4 {{ $etat['bar'] }} bg-white space-y-3 shadow-sm transition hover:shadow-md hover:-translate-y-0.5">

                {{-- RÉFÉRENCE ET ÉTAT --}}
                <div class="flex flex-wrap justify-between items-center gap-2">
                    <span class="font-bold text-xs text-forest-700 bg-emerald-100 px-2.5 py-1 rounded-md">
                        {{ $p->reference }}
                    </span>

                    <span class="shrink-0 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $etat['badge'] }}">
                        <i data-lucide="{{ $etat['icon'] }}" class="w-3.5 h-3.5"></i>
                        {{ $etat['label'] }}
                    </span>
                </div>

                {{-- PROCURANT --}}
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-slate-800 break-words">{{ $p->nom_procurant }}</p>
                    <p class="text-xs text-slate-500">CIP : {{ $p->cip_procurant }}</p>
                </div>

                {{-- PÉRIODE --}}
                <div class="inline-flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-2.5 py-1.5">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                    Du {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }}
                </div>

                {{-- ACTIONS --}}
                <div class="flex items-center gap-2 pt-1">

                    {{-- VOIR --}}
                    <a
                        href="{{ route('admin.procurations.pdf.view', $p->id) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex-1 min-w-0 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-forest-700 hover:bg-forest-900 text-white text-xs font-semibold rounded-lg transition-all"
                    >
                        <i data-lucide="eye" class="w-4 h-4 shrink-0"></i>
                        <span>Voir</span>
                    </a>

                    {{-- IMPRIMER (lance l'impression automatiquement) --}}
                    <button
                        type="button"
                        data-print-url="{{ route('admin.procurations.pdf.view', $p->id) }}"
                        class="btn-print flex-1 min-w-0 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-emerald-50 text-forest-700 border border-forest-700/30 text-xs font-semibold rounded-lg transition-all disabled:opacity-60 disabled:cursor-wait"
                        title="Imprimer la procuration {{ $p->reference }}"
                        aria-label="Imprimer la procuration {{ $p->reference }}"
                    >
                        <i data-lucide="printer" class="w-4 h-4 shrink-0 print-icon"></i>
                        <span class="print-spinner hidden w-4 h-4 shrink-0 rounded-full border-2 border-forest-700 border-t-transparent animate-spin"></span>
                        <span class="print-label">Imprimer</span>
                    </button>

                    {{-- TÉLÉCHARGER --}}
                    <a
                        href="{{ route('admin.procurations.pdf.download', $p->id) }}"
                        class="shrink-0 inline-flex items-center justify-center px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-semibold rounded-lg transition-all"
                        title="Télécharger le PDF"
                        aria-label="Télécharger le PDF de la procuration {{ $p->reference }}"
                    >
                        <i data-lucide="download" class="w-4 h-4"></i>
                    </a>

                </div>

            </article>

        @empty

            <div class="flex flex-col items-center justify-center text-center py-10 px-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
                <div class="w-12 h-12 rounded-full bg-white border border-slate-100 flex items-center justify-center mb-3">
                    <i data-lucide="file-text" class="w-6 h-6 text-slate-400"></i>
                </div>
                <p class="text-sm font-medium text-slate-600">Aucune procuration enregistrée</p>
                <p class="text-xs text-slate-400 mt-1">Les nouvelles procurations apparaîtront ici.</p>
            </div>

        @endforelse

    </div>

    {{-- PAGINATION --}}
    @if($procurations->hasPages())
        <div class="pt-3 border-t border-gray-100 overflow-x-auto">
            {{ $procurations->links() }}
        </div>
    @endif

</div>

            </div>
        </main>
    </div>
</div>


<script>
(function () {
    let frame = null;

    function setLoading(btn, loading) {
        btn.disabled = loading;
        btn.querySelector('.print-icon')?.classList.toggle('hidden', loading);
        btn.querySelector('.print-spinner')?.classList.toggle('hidden', !loading);
        const label = btn.querySelector('.print-label');
        if (label) label.textContent = loading ? 'Préparation…' : 'Imprimer';
    }

    function nettoyer() {
        if (frame) {
            URL.revokeObjectURL(frame.dataset.blob);
            frame.remove();
            frame = null;
        }
    }

    async function imprimer(btn) {
        const url = btn.dataset.printUrl;
        setLoading(btn, true);

        try {
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/pdf' }
            });

            const type = res.headers.get('Content-Type') || '';
            if (!res.ok || !type.includes('pdf')) {
                throw new Error('Réponse invalide');
            }

            const blobUrl = URL.createObjectURL(await res.blob());

            nettoyer();
            frame = document.createElement('iframe');
            frame.setAttribute('aria-hidden', 'true');
            frame.style.cssText =
                'position:fixed;right:0;bottom:0;width:1px;height:1px;border:0;opacity:0;pointer-events:none;';
            frame.dataset.blob = blobUrl;

            frame.onload = function () {
                setTimeout(function () {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } catch (e) {
                        window.open(blobUrl, '_blank');
                    }
                    setLoading(btn, false);
                }, 400);
            };

            frame.src = blobUrl;
            document.body.appendChild(frame);
        } catch (e) {
            // Session expirée ou erreur : on ouvre le PDF normalement
            setLoading(btn, false);
            window.open(url, '_blank');
        }
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-print-url]');
        if (!btn) return;
        e.preventDefault();
        imprimer(btn);
    });
})();
</script>


<script>
    lucide.createIcons();

    document.getElementById('add-action-btn').addEventListener('click', function() {
        const container = document.getElementById('actions-container');
        const row = document.createElement('div');
        row.className = 'action-row flex gap-2 items-center';
        row.innerHTML = `
            <input type="text" name="actions[]" placeholder="ex: Déposer le dossier" required class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm">
            <button type="button" class="remove-action p-2 bg-rose-100 text-rose-700 rounded-lg hover:bg-rose-200">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        `;
        container.appendChild(row);
        lucide.createIcons();
    });

    document.getElementById('actions-container').addEventListener('click', function(e) {
        if (e.target.closest('.remove-action')) {
            e.target.closest('.action-row').remove();
        }
    });
</script>
</body>
</html>
