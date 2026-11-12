<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planetech Administration - Attestations</title>
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
    <style>
        /* Feuille d'aperçu : reproduit le rendu du PDF */
        .doc { font-family: "Times New Roman", Times, serif; font-size: 13px; line-height: 1.75; color: #111; }
        .doc .num   { font-style: italic; font-size: 11.5px; margin: 0 0 12px; }
        .doc .haut  { text-align: right; font-style: italic; margin: 0 0 12px; }
        .doc .titre { text-align: center; font-size: 17px; font-weight: bold; text-decoration: underline; width: 85%; margin: 0 auto 14px; line-height: 1.4; }
        .doc .p     { text-align: justify; margin: 0 0 5px; }
        .doc .p.indent { text-indent: 24px; }
        .doc .fait  { text-align: right; font-style: italic; margin: 10px 0 0; }
        .doc .sig   { width: 50%; margin: 22px 0 0 auto; text-align: center; }
        .doc .sig .t { font-weight: bold; }
        .doc .sig .n { font-weight: bold; text-decoration: underline; margin-top: 38px; }
        .doc .ph    { background: #fef3c7; color: #92400e; border-radius: 3px; padding: 0 3px; font-style: normal; font-weight: normal; }
    </style>
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
                    <i data-lucide="file-check" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Gestion des Attestations</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Établissez des attestations de stage, de travail et de bonne exécution.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- ===================== FORMULAIRE ===================== --}}
                <div class="lg:col-span-6 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900 mb-5 flex items-center gap-2 border-b pb-3">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-forest-700"></i> Nouvelle Attestation
                    </h2>

                    <form action="{{ route('admin.attestations.store') }}" method="POST" class="space-y-4" id="attestation-form">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            {{-- TYPE --}}
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Type d'attestation *</label>
                                <select name="type" id="type" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
                                    @foreach($types as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- N° OFFICIEL (stages) --}}
                            <div data-types="fin_stage_professionnel fin_stage_academique">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">N° de l'attestation</label>
                                <input type="text" name="numero" placeholder="ex: 00218/2DWET/1TSARL/DG/AG/SA" value="{{ old('numero') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>

                            {{-- SOCIÉTÉ ÉMETTRICE --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Société émettrice *</label>
                                <input type="text" name="societe_emettrice" list="societes" required value="{{ old('societe_emettrice', 'PLANETECH SARL') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                                <datalist id="societes">
                                    <option value="PLANETECH SARL">
                                    <option value="WET SARL">
                                </datalist>
                            </div>

                            {{-- BÉNÉFICIAIRE --}}
                            <div class="md:col-span-2">
                                <label id="label-beneficiaire" class="block text-xs font-semibold text-slate-600 mb-1">Nom & Prénom du bénéficiaire *</label>
                                <input type="text" name="beneficiaire" required value="{{ old('beneficiaire') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>

                            {{-- GENRE --}}
                            <div data-types="fin_stage_professionnel fin_stage_academique travail">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Genre *</label>
                                <select name="genre" data-req="1" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
                                    <option value="F" @selected(old('genre') === 'F')>Féminin (la nommée)</option>
                                    <option value="M" @selected(old('genre') === 'M')>Masculin (le nommé)</option>
                                </select>
                            </div>

                            {{-- POSTE / DOMAINE --}}
                            <div data-types="fin_stage_professionnel fin_stage_academique travail">
                                <label id="label-poste" class="block text-xs font-semibold text-slate-600 mb-1">Poste occupé *</label>
                                <input type="text" name="poste" id="poste" data-req="1" value="{{ old('poste') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>

                            {{-- DATES --}}
                            <div data-types="fin_stage_professionnel fin_stage_academique travail">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Du (Début) *</label>
                                <input type="date" name="date_debut" data-req="1" value="{{ old('date_debut') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div data-types="fin_stage_professionnel fin_stage_academique travail">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Au (Fin) *</label>
                                <input type="date" name="date_fin" data-req="1" value="{{ old('date_fin') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                                <div class="flex flex-wrap gap-1.5 mt-1.5">
                                    @foreach([1, 2, 3, 6, 12] as $m)
                                        <button type="button" data-add-months="{{ $m }}" class="px-2 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-semibold">+{{ $m }} mois</button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- DURÉE (travail) --}}
                            <div class="md:col-span-2" data-types="travail">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-slate-600">Durée (facultatif)</label>
                                    <button type="button" id="calc-duree" class="text-[11px] font-semibold text-forest-700 hover:underline">Calculer depuis les dates</button>
                                </div>
                                <input type="text" name="duree" placeholder="ex: deux (02) ans" value="{{ old('duree') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>

                            {{-- TRAVAUX --}}
                            <div class="md:col-span-2" data-types="bonne_fin_travaux">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Travaux réalisés *</label>
                                <input type="text" name="objet" data-req="1" placeholder="ex: installation du réseau WIFI sans fil" value="{{ old('objet') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                            <div data-types="bonne_fin_travaux">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Période des travaux *</label>
                                <input type="text" name="periode_texte" data-req="1" placeholder="ex: Septembre 2017" value="{{ old('periode_texte') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>

                            {{-- DATE D'ÉTABLISSEMENT --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Date d'établissement *</label>
                                <input type="date" name="date_etablissement" required value="{{ old('date_etablissement', now()->timezone('Africa/Porto-Novo')->format('Y-m-d')) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm">
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 bg-forest-700 hover:bg-forest-900 text-white font-medium rounded-xl shadow-md transition-all flex items-center justify-center gap-2 mt-4">
                            <i data-lucide="file-check" class="w-4 h-4"></i> Enregistrer l'attestation
                        </button>
                    </form>
                </div>

                {{-- ===================== APERÇU / HISTORIQUE ===================== --}}
                <div class="lg:col-span-6 min-w-0 bg-white rounded-2xl border border-gray-100 p-5 lg:p-6 shadow-sm space-y-4">

                    {{-- ONGLETS --}}
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3">
                        <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-semibold">
                            <button type="button" data-tab="apercu" class="tab-btn px-3.5 py-2 rounded-lg flex items-center gap-1.5">
                                <i data-lucide="eye" class="w-4 h-4"></i> Aperçu en direct
                            </button>
                            <button type="button" data-tab="historique" class="tab-btn px-3.5 py-2 rounded-lg flex items-center gap-1.5">
                                <i data-lucide="history" class="w-4 h-4"></i> Historique
                                <span class="inline-flex items-center rounded-full bg-forest-50 text-forest-700 text-[10px] font-bold px-2 py-0.5">{{ $attestations->total() }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- APERÇU --}}
                    <div id="tab-apercu" class="space-y-3">
                        <p class="text-xs text-slate-500">L'aperçu se met à jour pendant la saisie. Les zones en jaune sont les champs encore vides.</p>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div id="preview" class="doc bg-white shadow-sm rounded-md px-8 py-8 min-h-[420px]"></div>
                        </div>
                    </div>

                    {{-- HISTORIQUE --}}
                    <div id="tab-historique" class="hidden space-y-3">
                        @forelse($attestations as $a)
                            <article class="group p-4 rounded-xl border border-slate-100 border-l-4 border-l-emerald-500 bg-white space-y-3 shadow-sm transition hover:shadow-md hover:-translate-y-0.5">

                                <div class="flex flex-wrap justify-between items-center gap-2">
                                    <span class="font-bold text-xs text-forest-700 bg-emerald-100 px-2.5 py-1 rounded-md">{{ $a->reference }}</span>
                                    <span class="shrink-0 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold bg-forest-50 text-forest-700">
                                        <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
                                        {{ $a->type_label }}
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-slate-800 break-words">{{ $a->beneficiaire }}</p>
                                    <p class="text-xs text-slate-500">{{ $a->societe_emettrice }}</p>
                                </div>

                                <div class="inline-flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-2.5 py-1.5">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                    Établie le {{ $a->date_etablissement->format('d/m/Y') }}
                                </div>

                                <div class="flex items-center gap-2 pt-1">
                                    <a href="{{ route('admin.attestations.pdf.view', $a->id) }}" target="_blank" rel="noopener noreferrer"
                                       class="flex-1 min-w-0 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-forest-700 hover:bg-forest-900 text-white text-xs font-semibold rounded-lg transition-all">
                                        <i data-lucide="eye" class="w-4 h-4 shrink-0"></i>
                                        <span>Voir</span>
                                    </a>

                                    <button type="button" data-print-url="{{ route('admin.attestations.pdf.view', $a->id) }}"
                                            class="btn-print flex-1 min-w-0 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white hover:bg-emerald-50 text-forest-700 border border-forest-700/30 text-xs font-semibold rounded-lg transition-all disabled:opacity-60 disabled:cursor-wait"
                                            title="Imprimer {{ $a->reference }}">
                                        <i data-lucide="printer" class="w-4 h-4 shrink-0 print-icon"></i>
                                        <span class="print-spinner hidden w-4 h-4 shrink-0 rounded-full border-2 border-forest-700 border-t-transparent animate-spin"></span>
                                        <span class="print-label">Imprimer</span>
                                    </button>

                                    <a href="{{ route('admin.attestations.pdf.download', $a->id) }}"
                                       class="shrink-0 inline-flex items-center justify-center px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-semibold rounded-lg transition-all"
                                       title="Télécharger le PDF">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="flex flex-col items-center justify-center text-center py-10 px-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
                                <div class="w-12 h-12 rounded-full bg-white border border-slate-100 flex items-center justify-center mb-3">
                                    <i data-lucide="file-check" class="w-6 h-6 text-slate-400"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-600">Aucune attestation enregistrée</p>
                                <p class="text-xs text-slate-400 mt-1">Les nouvelles attestations apparaîtront ici.</p>
                            </div>
                        @endforelse

                        @if($attestations->hasPages())
                            <div class="pt-3 border-t border-gray-100 overflow-x-auto">
                                {{ $attestations->links() }}
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

{{-- ===================== IMPRESSION DIRECTE ===================== --}}
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
            const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/pdf' } });
            const type = res.headers.get('Content-Type') || '';
            if (!res.ok || !type.includes('pdf')) throw new Error('Réponse invalide');

            const blobUrl = URL.createObjectURL(await res.blob());
            nettoyer();
            frame = document.createElement('iframe');
            frame.setAttribute('aria-hidden', 'true');
            frame.style.cssText = 'position:fixed;right:0;bottom:0;width:1px;height:1px;border:0;opacity:0;pointer-events:none;';
            frame.dataset.blob = blobUrl;
            frame.onload = function () {
                setTimeout(function () {
                    try { frame.contentWindow.focus(); frame.contentWindow.print(); }
                    catch (e) { window.open(blobUrl, '_blank'); }
                    setLoading(btn, false);
                }, 400);
            };
            frame.src = blobUrl;
            document.body.appendChild(frame);
        } catch (e) {
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

{{-- ===================== FORMULAIRE DYNAMIQUE + APERÇU ===================== --}}
<script>
    lucide.createIcons();

    const form = document.getElementById('attestation-form');
    const typeSelect = document.getElementById('type');

    /* ---------- Libellés selon le type ---------- */
    const CONFIG = {
        fin_stage_professionnel: { benef: 'Nom & Prénom du stagiaire *',  poste: 'Poste occupé durant le stage *', holder: 'ex: comptable stagiaire' },
        fin_stage_academique:    { benef: 'Nom & Prénom du stagiaire *',  poste: 'Domaine du stage *',             holder: 'ex: comptabilité Audit et Contrôle de Gestion' },
        travail:                 { benef: 'Nom & Prénom de l\'employé *', poste: 'Poste occupé *',                 holder: 'ex: chauffeur' },
        bonne_fin_travaux:       { benef: 'Société bénéficiaire *',       poste: '',                               holder: '' }
    };

    const TITRES = {
        fin_stage_professionnel: 'ATTESTATION DE FIN DE STAGE PROFESSIONNEL',
        fin_stage_academique:    'ATTESTATION DE FIN DE STAGE ACADEMIQUE',
        travail:                 'ATTESTATION DE TRAVAIL',
        bonne_fin_travaux:       'ATTESTATION DE BONNE FIN D\'EXECUTION DES TRAVAUX'
    };

    function appliquerType() {
        const type = typeSelect.value;
        const cfg = CONFIG[type];

        document.querySelectorAll('[data-types]').forEach(function (bloc) {
            const visible = bloc.dataset.types.split(' ').includes(type);
            bloc.classList.toggle('hidden', !visible);
            bloc.querySelectorAll('input, select').forEach(function (champ) {
                champ.disabled = !visible;
                champ.required = visible && champ.dataset.req === '1';
            });
        });

        document.getElementById('label-beneficiaire').textContent = cfg.benef;
        document.getElementById('label-poste').textContent = cfg.poste;
        document.getElementById('poste').placeholder = cfg.holder;
        render();
    }

    /* ---------- Outils dates ---------- */
    const MOIS = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    const pad  = n => String(n).padStart(2, '0');
    const cap  = s => s.charAt(0).toUpperCase() + s.slice(1);

    function parse(iso) {
        if (!iso) return null;
        const p = iso.split('-').map(Number);
        return { y: p[0], m: p[1], j: p[2] };
    }
    const dateMaj = d => pad(d.j) + ' ' + MOIS[d.m - 1].toUpperCase() + ' ' + d.y;
    const dateCap = d => pad(d.j) + ' ' + cap(MOIS[d.m - 1]) + ' ' + d.y;
    const dateMin = d => d.j + ' ' + MOIS[d.m - 1] + ' ' + d.y;

    function addMonths(iso, n) {
        const p = iso.split('-').map(Number);
        const d = new Date(Date.UTC(p[0], p[1] - 1 + n, p[2]));
        if (d.getUTCDate() !== p[2]) d.setUTCDate(0);   // 31 janv + 1 mois → 28/29 févr
        return d.toISOString().slice(0, 10);
    }

    function moisEntre(a, b) {
        const A = parse(a), B = parse(b);
        let m = (B.y - A.y) * 12 + (B.m - A.m);
        if (B.j < A.j) m--;
        return Math.max(m, 0);
    }

    /* ---------- Nombres en lettres (0 à 99) ---------- */
    const U = ['zéro','un','deux','trois','quatre','cinq','six','sept','huit','neuf','dix','onze','douze','treize','quatorze','quinze','seize','dix-sept','dix-huit','dix-neuf'];
    const T = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante'];
    function mots(n) {
        if (n < 20) return U[n];
        if (n < 70) { const d = Math.floor(n / 10), u = n % 10; return T[d] + (u === 1 ? ' et un' : (u ? '-' + U[u] : '')); }
        if (n < 80) { const u = n - 60; return 'soixante' + (u === 11 ? ' et onze' : '-' + U[u]); }
        if (n === 80) return 'quatre-vingts';
        if (n < 100) return 'quatre-vingt-' + U[n - 80];
        return String(n);
    }
    const nb = n => mots(n) + ' (' + pad(n) + ')';

    function dureeTexte(mois) {
        const a = Math.floor(mois / 12), m = mois % 12, parts = [];
        if (a > 0) parts.push(nb(a) + (a > 1 ? ' ans' : ' an'));
        if (m > 0) parts.push(nb(m) + ' mois');
        return parts.join(' et ') || '';
    }

    /* ---------- Aperçu en direct (même texte que le PDF) ---------- */
    const esc = s => s.replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    function v(name) {
        const el = form.elements[name];
        return el && !el.disabled ? el.value.trim() : '';
    }
    function champ(name, label, gras) {
        const x = v(name);
        const t = x ? esc(x) : '<span class="ph">' + label + '</span>';
        return gras && x ? '<strong>' + t + '</strong>' : (gras ? t : t);
    }
    function dt(name, fmt, label) {
        const d = parse(v(name));
        return d ? fmt(d) : '<span class="ph">' + label + '</span>';
    }

    function render() {
        const type = typeSelect.value;
        const f = v('genre') !== 'M';
        const nomme = f ? 'la nommée' : 'le nommé';
        const interesse = f ? 'l\'intéressée' : 'l\'intéressé';
        const acq = f ? 'acquittée' : 'acquitté';
        const pronom = f ? 'Elle' : 'Il';

        const soc = champ('societe_emettrice', 'société', true);
        const ben = champ('beneficiaire', 'nom', true);
        const poste = champ('poste', 'poste');
        const dg = 'Armelle ASSOGBA';

        let haut = '', corps = '', bas = '', indent = '';

        // période des stages : "19 JUIN AU 19 SEPTEMBRE 2026"
        let periode = '<span class="ph">période</span>';
        const d1 = parse(v('date_debut')), d2 = parse(v('date_fin'));
        if (d1 && d2) {
            const debut = pad(d1.j) + ' ' + MOIS[d1.m - 1].toUpperCase() + (d1.y !== d2.y ? ' ' + d1.y : '');
            periode = debut + ' AU ' + dateMaj(d2);
        }

        const dateFait = dt('date_etablissement', dateCap, 'date');

        if (type === 'fin_stage_professionnel' || type === 'fin_stage_academique') {
            const num = v('numero');
            haut = num ? '<p class="num">N° ' + esc(num) + '</p>' : '';
            const pro = type === 'fin_stage_professionnel';
            corps =
                '<p class="p">Je soussigné, ' + dg + ' Directeur Général de la Société ' + soc + ' atteste que ' + nomme + ' ' + ben +
                ' a effectué un stage ' + (pro ? 'professionnel sous ma tutelle en tant que ' : 'académique sous ma tutelle en ') + poste +
                ' pour la période du <strong>' + periode + '</strong>.</p>' +
                '<p class="p">Au cours de son stage, ' + interesse + ' s\'est ' + acq + ' des tâches qui lui ont été confiées avec dévouement et assiduité.</p>' +
                '<p class="p">' + pronom + ' a également fait preuve d\'une disponibilité permanente et d\'un esprit de collaboration remarquable.</p>' +
                '<p class="p">En foi de quoi, la présente <strong>' + (pro ? 'ATTESTATION' : 'ATTESTATION DE FIN DE STAGE ACADEMIQUE') + '</strong> lui est délivrée pour servir et valoir ce que de droit.</p>';
            bas = '<p class="fait">Fait à COTONOU, le ' + dateFait + '</p>';

        } else if (type === 'travail') {
            indent = ' indent';
            haut = '<p class="haut">Cotonou, le ' + dateFait + '</p>';
            const duree = v('duree');
            corps =
                '<p class="p' + indent + '">Je soussigné, ' + dg + ', Directeur Général de la Société ' + soc + ', sise à Houéyiho/Cotonou, atteste que ' + nomme + ' ' + ben +
                ' a travaillé en tant que ' + poste + (duree ? ' pour une période de <strong>' + esc(duree) + '</strong>' : '') +
                ' pour le compte de l\'entreprise du <strong>' + dt('date_debut', dateMin, 'début') + ' au ' + dt('date_fin', dateMin, 'fin') + '</strong>.</p>' +
                '<p class="p' + indent + '">Pendant son passage au sein de l\'entreprise, ' + interesse + ' s\'est ' + acq + ' des tâches qui lui ont été confiées avec dévouement et assiduité.</p>' +
                '<p class="p' + indent + '">' + pronom + ' a également fait preuve d\'une disponibilité permanente et d\'un esprit de collaboration remarquable.</p>' +
                '<p class="p' + indent + '">En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.</p>';

        } else {
            indent = ' indent';
            const objet = v('objet');
            const elision = /^[aeiouyàâéèêëîïôûh]/i.test(objet) ? 'd\'' : 'de ';
            corps =
                '<p class="p' + indent + '">Je soussigné, <strong>' + dg + '</strong>, le Directeur Général de la Société ' + soc + ', sise à Houéyiho/Cotonou, atteste par la présente que la Société «&nbsp;' + ben + '&nbsp;» a fait des travaux ' +
                (objet ? elision + esc(objet) : '<span class="ph">travaux réalisés</span>') + ' en ' + (v('periode_texte') ? '<strong>' + esc(v('periode_texte')) + '</strong>' : '<span class="ph">période</span>') + '.</p>' +
                '<p class="p' + indent + '">En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.</p>';
            bas = '<p class="fait">Fait à Cotonou, le ' + dateFait + '</p>';
        }

        document.getElementById('preview').innerHTML =
            haut +
            '<div class="titre">' + TITRES[type] + '</div>' +
            corps + bas +
            '<div class="sig"><div class="t">Le Directeur Général,</div><div class="n">' + dg + '</div></div>';
    }

    /* ---------- Boutons rapides ---------- */
    document.querySelectorAll('[data-add-months]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const debut = form.elements['date_debut'];
            if (!debut.value) { debut.focus(); return; }
            form.elements['date_fin'].value = addMonths(debut.value, Number(btn.dataset.addMonths));
            render();
        });
    });

    document.getElementById('calc-duree').addEventListener('click', function () {
        const a = form.elements['date_debut'].value, b = form.elements['date_fin'].value;
        if (!a || !b) return;
        form.elements['duree'].value = dureeTexte(moisEntre(a, b));
        render();
    });

    /* ---------- Onglets ---------- */
    function ouvrirOnglet(nom) {
        ['apercu', 'historique'].forEach(function (t) {
            document.getElementById('tab-' + t).classList.toggle('hidden', t !== nom);
        });
        document.querySelectorAll('.tab-btn').forEach(function (b) {
            const actif = b.dataset.tab === nom;
            b.classList.toggle('bg-white', actif);
            b.classList.toggle('shadow-sm', actif);
            b.classList.toggle('text-forest-700', actif);
            b.classList.toggle('text-slate-500', !actif);
        });
    }
    document.querySelectorAll('.tab-btn').forEach(function (b) {
        b.addEventListener('click', function () { ouvrirOnglet(b.dataset.tab); });
    });

    /* ---------- Démarrage ---------- */
    form.addEventListener('input', render);
    form.addEventListener('change', render);
    typeSelect.addEventListener('change', appliquerType);

    appliquerType();
    // Après un enregistrement réussi ou une pagination, on affiche l'historique
    ouvrirOnglet('{{ session('success') || request()->has('page') ? 'historique' : 'apercu' }}');
</script>
</body>
</html>