<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planetech Administration - Décharges</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: { 50: '#f2f7f4', 700: '#1b4d3e', 900: '#0f2c23' },
                        signal: '#1b4d3e', ink: '#111827', 'slate-muted': '#6b7280'
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
</head>
<body class="bg-gray-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">
    @include('admin.aside')

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <main class="p-8 space-y-6">

            @if(session('success'))
                <div class="px-4 py-3 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 font-medium text-sm flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="px-4 py-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-800 font-medium text-sm flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i> {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="px-4 py-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-800 text-sm">
                    <p class="font-semibold mb-1">Veuillez corriger les erreurs suivantes :</p>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- EN-TÊTE --}}
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-2xl bg-forest-700 text-white flex items-center justify-center shadow-lg shadow-forest-700/20">
                    <i data-lucide="file-signature" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Gestion des Décharges</h1>
                    <p class="text-sm text-slate-muted mt-0.5">Établissez des fiches de décharge multi-matériels signées numériquement.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- FORMULAIRE --}}
                <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900 mb-5 flex items-center gap-2 border-b pb-3">
                        <i data-lucide="pen-tool" class="w-5 h-5 text-forest-700"></i> Nouvelle Décharge
                    </h2>

                    <form action="{{ route('admin.decharges.store') }}" method="POST" id="decharge-form" class="space-y-5">
                        @csrf

                        {{-- TYPE DE DECHARGE --}}
                        @php $oldType = old('type', 'argent'); @endphp
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Type de Décharge</label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="flex items-center justify-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-emerald-50/50 has-[:checked]:border-forest-700 has-[:checked]:bg-emerald-50 transition-all">
                                    <input type="radio" name="type" value="argent" class="sr-only" {{ $oldType === 'argent' ? 'checked' : '' }} onchange="toggleTypeFields('argent')">
                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2"><i data-lucide="banknote" class="w-4 h-4 text-emerald-600"></i> Argent</span>
                                </label>
                                <label class="flex items-center justify-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-emerald-50/50 has-[:checked]:border-forest-700 has-[:checked]:bg-emerald-50 transition-all">
                                    <input type="radio" name="type" value="materiel" class="sr-only" {{ $oldType === 'materiel' ? 'checked' : '' }} onchange="toggleTypeFields('materiel')">
                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2"><i data-lucide="box" class="w-4 h-4 text-emerald-600"></i> Matériel / Doc</span>
                                </label>
                                <label class="flex items-center justify-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-emerald-50/50 has-[:checked]:border-forest-700 has-[:checked]:bg-emerald-50 transition-all">
                                    <input type="radio" name="type" value="responsabilite" class="sr-only" {{ $oldType === 'responsabilite' ? 'checked' : '' }} onchange="toggleTypeFields('responsabilite')">
                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i> Responsabilité</span>
                                </label>
                            </div>
                        </div>

                        {{-- IDENTITÉ BÉNÉFICIAIRE --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Nom & Prénom *</label>
                                <input type="text" name="nom" value="{{ old('nom') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-forest-700 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Rôle *</label>
                                <input type="text" name="role" value="{{ old('role') }}" placeholder="ex: Employé, Prestataire" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-forest-700 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Entreprise</label>
                                <input type="text" name="entreprise" value="{{ old('entreprise') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-forest-700 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Téléphone *</label>
                                <input type="text" name="numero" value="{{ old('numero') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-forest-700 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Adresse E-mail *</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-forest-700 text-sm">
                            </div>
                        </div>

                        {{-- SECTION ARGENT --}}
                        <div id="fields-argent" class="space-y-4 pt-2 border-t">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Somme (FCFA) *</label>
                                    <input type="number" name="somme" value="{{ old('somme') }}" min="0" step="any" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-forest-700 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Motif *</label>
                                    <input type="text" name="motif" value="{{ old('motif') }}" placeholder="ex: Achat d'outillage" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-forest-700 text-sm">
                                </div>
                            </div>
                        </div>

                        {{-- SECTION MATÉRIEL (LISTE DYNAMIQUE) --}}
                        <div id="fields-materiel" class="space-y-4 pt-2 border-t hidden">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Liste des Matériels / Documents</label>
                                <button type="button" id="add-materiel-btn" class="px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-semibold rounded-lg flex items-center gap-1 transition-all">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Ajouter un matériel
                                </button>
                            </div>

                            <div id="materiels-container" class="space-y-3">
                                <div class="materiel-row p-3 rounded-xl border border-slate-200 bg-slate-50/50 relative grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Désignation *</label>
                                        <input type="text" name="materiels[0][designation]" placeholder="ex: Routeur Neo" required class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Quantité *</label>
                                        <input type="number" name="materiels[0][quantite]" value="1" min="1" required class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm">
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">État *</label>
                                        <select name="materiels[0][etat]" class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm">
                                            <option value="neuf">Neuf</option>
                                            <option value="partiellement_utilise">Partiellement utilisé</option>
                                            <option value="vieux">Vieux</option>
                                            <option value="defectueux">Défectueux</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-1 flex justify-center pb-1">
                                        {{-- Pas de bouton supprimer sur la première ligne --}}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- SECTION RESPONSABILITÉ --}}
                        <div id="fields-responsabilite" class="space-y-4 pt-2 border-t hidden">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Description / Engagement *</label>
                                <textarea name="description" rows="3" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-forest-700 text-sm" placeholder="Saisir les conditions précises...">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        {{-- ZONE SIGNATURE NUMÉRIQUE --}}
                        <div class="pt-2 border-t">
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-xs font-bold text-slate-700 uppercase">Signature Numérique *</label>
                                <button type="button" id="clear-signature" class="text-xs text-rose-600 hover:underline">Effacer</button>
                            </div>
                            <div class="border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 relative h-36">
                                <canvas id="signature-pad" class="w-full h-full rounded-xl cursor-crosshair"></canvas>
                            </div>
                            <input type="hidden" name="signature_data" id="signature-input">
                        </div>

                        <button type="submit" id="submit-btn" class="w-full py-3 bg-forest-700 hover:bg-forest-900 text-white font-medium rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i> Valider et envoyer par e-mail
                        </button>
                    </form>
                </div>

                {{-- HISTORIQUE DES DECHARGES --}}
                             {{-- HISTORIQUE DES DECHARGES --}}
                <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                    <h2 class="text-lg font-semibold text-slate-900 border-b pb-3 flex items-center justify-between">
                        <span>Historique Récents</span>
                        <i data-lucide="history" class="w-5 h-5 text-slate-400"></i>
                    </h2>

                    <div class="space-y-3">
                        @forelse($decharges as $d)
                            <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/50 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-xs text-forest-700 bg-emerald-100 px-2 py-0.5 rounded-md">{{ $d->reference }}</span>
                                    <span class="text-xs text-slate-400">{{ $d->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $d->nom }}</p>
                                    <p class="text-xs text-slate-500 capitalize">Type: {{ $d->type }}</p>
                                </div>
                                @if($d->type === 'materiel' && $d->items && $d->items->count() > 0)
                                    <div class="text-xs bg-white p-2 rounded-lg border border-slate-200 text-slate-600 space-y-1">
                                        @foreach($d->items as $item)
                                            <div>• {{ $item->designation }} (Qté: {{ $item->quantite }})</div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- ACTIONS PDF --}}
                                <div class="flex gap-2 pt-1">
                                    <a href="{{ route('admin.decharges.pdf', $d) }}" target="_blank"
                                       class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-forest-700 hover:bg-forest-900 text-white text-xs font-semibold rounded-lg transition-all">
                                        <i data-lucide="file-text" class="w-4 h-4"></i> Voir le PDF
                                    </a>
                                    <a href="{{ route('admin.decharges.pdf', ['decharge' => $d, 'download' => 1]) }}"
                                       class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-semibold rounded-lg transition-all"
                                       title="Télécharger">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400 text-center py-6">Aucune décharge enregistrée.</p>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        {{ $decharges->links() }}
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
    lucide.createIcons();

    // 1. BASCULE DE TYPE : affiche la section choisie et DÉSACTIVE les champs des autres
    //    (un champ désactivé n'est pas envoyé, donc plus d'erreur de validation parasite)
    function toggleTypeFields(selectedType) {
        ['argent', 'materiel', 'responsabilite'].forEach(function (k) {
            const section = document.getElementById('fields-' + k);
            const active = (k === selectedType);
            section.classList.toggle('hidden', !active);
            section.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = !active;
            });
        });
    }
    toggleTypeFields(document.querySelector('input[name="type"]:checked').value);

    // 2. LIGNES MULTIPLES DE MATÉRIEL (bouton +)
    let materielIndex = 1;
    document.getElementById('add-materiel-btn').addEventListener('click', function () {
        const container = document.getElementById('materiels-container');
        const row = document.createElement('div');
        row.className = 'materiel-row p-3 rounded-xl border border-slate-200 bg-slate-50/50 relative grid grid-cols-1 md:grid-cols-12 gap-3 items-end';
        row.innerHTML = `
            <div class="md:col-span-6">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Désignation *</label>
                <input type="text" name="materiels[${materielIndex}][designation]" placeholder="ex: Caméra Solaire" class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Quantité *</label>
                <input type="number" name="materiels[${materielIndex}][quantite]" value="1" min="1" class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm" required>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-600 mb-1">État *</label>
                <select name="materiels[${materielIndex}][etat]" class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm">
                    <option value="neuf">Neuf</option>
                    <option value="partiellement_utilise">Partiellement utilisé</option>
                    <option value="vieux">Vieux</option>
                    <option value="defectueux">Défectueux</option>
                </select>
            </div>
            <div class="md:col-span-1 flex justify-center pb-1">
                <button type="button" class="remove-materiel p-2 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-lg transition-all" title="Supprimer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        `;
        container.appendChild(row);
        materielIndex++;
    });

    // Suppression d'une ligne dynamique
    document.getElementById('materiels-container').addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-materiel');
        if (btn) {
            btn.closest('.materiel-row').remove();
        }
    });

    // 3. SIGNATURE PAD
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255, 255, 255, 0)' });

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        signaturePad.clear();
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    document.getElementById('clear-signature').addEventListener('click', function () {
        signaturePad.clear();
    });

    // 4. ENVOI DU FORMULAIRE
    document.getElementById('decharge-form').addEventListener('submit', function (e) {
        if (signaturePad.isEmpty()) {
            e.preventDefault();
            alert('Veuillez apposer votre signature avant de valider la décharge.');
            return false;
        }
        document.getElementById('signature-input').value = signaturePad.toDataURL('image/png');

        // Évite le double envoi (et donc les doubles e-mails)
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.classList.add('opacity-70');
    });
</script>
</body>
</html>
