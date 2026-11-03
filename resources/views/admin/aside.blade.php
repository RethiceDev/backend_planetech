@php
    $admin = Auth::guard('admin')->user();

    $lienBase    = 'nav-link flex items-center gap-3 px-4 py-3 rounded-2xl font-medium text-sm transition-all duration-200';
    $lienActif   = 'bg-forest-700 text-white shadow-md font-semibold';
    $lienInactif = 'text-emerald-100/70 hover:bg-forest-700/50 hover:text-white';

    $classe = fn (bool $actif) => $lienBase . ' ' . ($actif ? $lienActif : $lienInactif);
    $current = fn (bool $actif) => $actif ? 'aria-current="page"' : '';

    // Espace contrôle : le lien devient actif dès que la route existe
    $urlEmployes  = \Illuminate\Support\Facades\Route::has('admin.employes.index')  ? route('admin.employes.index')  : null;
    $urlPresences = \Illuminate\Support\Facades\Route::has('admin.presences.index') ? route('admin.presences.index') : null;
@endphp

{{-- Bouton menu visible sur téléphone --}}
<button
    id="admin-sidebar-toggle"
    type="button"
    aria-label="Ouvrir le menu de navigation"
    aria-controls="admin-sidebar"
    aria-expanded="false"
    class="fixed left-4 top-4 z-[60] inline-flex h-11 w-11 items-center justify-center
           rounded-xl bg-forest-900 text-white shadow-lg transition hover:bg-forest-700
           focus:outline-none focus:ring-2 focus:ring-emerald-400 md:hidden"
>
    <i data-lucide="menu" class="h-5 w-5"></i>
</button>

{{-- Fond sombre derrière le menu sur téléphone --}}
<div
    id="admin-sidebar-backdrop"
    class="fixed inset-0 z-40 hidden bg-black/50 md:hidden"
    aria-hidden="true"
></div>

<aside
    id="admin-sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-64 max-w-[85vw] flex-col justify-between
           overflow-y-auto bg-forest-900 text-white shadow-xl
           -translate-x-full transition-transform duration-300 ease-in-out
           md:static md:z-auto md:h-screen md:max-w-none md:translate-x-0
           md:shrink-0 md:overflow-y-auto md:transition-none"
>

    {{-- ========================================================= --}}
    {{-- PARTIE HAUTE --}}
    {{-- ========================================================= --}}
    <div>

        {{-- LOGO / PROFIL ADMIN --}}
        <a
            href="{{ route('admin.profil') }}"
            class="block p-6 transition-all duration-200 hover:bg-forest-700/40"
        >
            <div class="flex items-center gap-3">
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="Logo PLANETECH"
                    class="h-16 w-auto object-contain"
                >

                <div class="min-w-0">
                    <h1 class="font-bold tracking-wide text-sm">PLANETECH</h1>
                    <p class="text-xs text-emerald-400/80 truncate">
                        {{ $admin->prenom }} {{ $admin->nom }}
                    </p>
                </div>
            </div>
        </a>


        {{-- NAVIGATION --}}
        <nav class="mt-2 px-3 pb-4 space-y-1.5" id="admin-sidebar-nav">

            {{-- ===== GÉNÉRAL ===== --}}
            @php $a = request()->routeIs('admin.accueil'); @endphp
            <a href="{{ route('admin.accueil') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>Vue d'ensemble</span>
            </a>


            {{-- ===== COMMERCIAL ===== --}}
            <p class="px-4 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-300/50">
                Commercial
            </p>

            @php $a = request()->routeIs('admin.devis*', 'admin.demandes.*'); @endphp
            <a href="{{ route('admin.devis') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="file-text" class="w-5 h-5"></i>
                <span>Devis</span>
            </a>

            @php $a = request()->routeIs('admin.commandes', 'commande.*'); @endphp
            <a href="{{ route('admin.commandes') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                <span>Bons de commande</span>
            </a>


            {{-- ===== CATALOGUE ===== --}}
            <p class="px-4 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-300/50">
                Catalogue
            </p>

            @php $a = request()->routeIs('categories.*'); @endphp
            <a href="{{ route('categories.index') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="tags" class="w-5 h-5"></i>
                <span>Catégories</span>
            </a>

            @php $a = request()->routeIs('sous-categories.*'); @endphp
            <a href="{{ route('sous-categories.index') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="list-tree" class="w-5 h-5"></i>
                <span>Sous-catégories</span>
            </a>

            @php $a = request()->routeIs('produits.*'); @endphp
            <a href="{{ route('produits.index') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="store" class="w-5 h-5"></i>
                <span>Boutique</span>
            </a>

            @php $a = request()->routeIs('realisations.*'); @endphp
            <a href="{{ route('realisations.index') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="image" class="w-5 h-5"></i>
                <span>Réalisations</span>
            </a>


            {{-- ===== DOCUMENTS ===== --}}
            <p class="px-4 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-300/50">
                Documents
            </p>

            @php $a = request()->routeIs('admin.decharges.*'); @endphp
            <a href="{{ route('admin.decharges.index') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="file-check-2" class="w-5 h-5"></i>
                <span>Décharges</span>
            </a>

            @php $a = request()->routeIs('admin.procurations.*'); @endphp
            <a href="{{ route('admin.procurations.index') }}" class="{{ $classe($a) }}" {!! $current($a) !!}>
                <i data-lucide="file-signature" class="w-5 h-5"></i>
                <span>Procurations</span>
            </a>


            {{-- ===== ESPACE CONTRÔLE ===== --}}
            <p class="px-4 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-300/50">
                Espace contrôle
            </p>

            <div
                class="flex items-center gap-3 px-4 py-3 rounded-2xl font-medium text-sm
                    text-emerald-100/40 cursor-not-allowed select-none"
                aria-disabled="true"
                title="Bientôt disponible"
                >
                <i data-lucide="users" class="w-5 h-5"></i>
                <span>Employés</span>
                <span class="ml-auto rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                    Bientôt
                </span>
            </div>

            <div
                class="flex items-center gap-3 px-4 py-3 rounded-2xl font-medium text-sm
                    text-emerald-100/40 cursor-not-allowed select-none"
                aria-disabled="true"
                title="Bientôt disponible"
                >
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
                <span>Présences</span>
                <span class="ml-auto rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                    Bientôt
                </span>
            </div>


            {{-- ===================================================== --}}
            {{-- AUTRES RUBRIQUES (désactivées) --}}
            {{-- ===================================================== --}}

            {{--
            <a href="{{ route('domaines.index') }}" class="{{ $classe(request()->routeIs('domaines.*')) }}">
                <i data-lucide="wrench" class="w-5 h-5"></i>
                <span>Domaines</span>
            </a>

            <a href="{{ route('equipes.index') }}" class="{{ $classe(request()->routeIs('equipes.*')) }}">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span>Équipe</span>
            </a>

            <a href="{{ route('partenaires.index') }}" class="{{ $classe(request()->routeIs('partenaires.*')) }}">
                <i data-lucide="building-2" class="w-5 h-5"></i>
                <span>Partenaires</span>
            </a>
            --}}

        </nav>

    </div>


    {{-- ========================================================= --}}
    {{-- FOOTER SIDEBAR --}}
    {{-- ========================================================= --}}
    <div class="p-4 border-t border-forest-700/40 space-y-1">

        {{-- VOIR LE SITE --}}
        <a
            href="https://planetechsarl.com"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl
                   text-emerald-100/70 hover:bg-forest-700/30
                   hover:text-white text-sm transition-all duration-200"
         >
            <i data-lucide="globe" class="w-4 h-4"></i>
            <span>Voir le site public</span>
        </a>

        {{-- DÉCONNEXION --}}
        <form action="{{ route('admin.logout') }}" method="POST" class="w-full">
            @csrf

            <button
                type="submit"
                class="flex w-full items-center gap-3 px-3 py-2.5 rounded-xl
                       text-red-300 hover:bg-red-500/10
                       hover:text-red-200 text-sm transition-all duration-200"
            >
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Se déconnecter</span>
            </button>
        </form>

    </div>

</aside>


{{-- ============================================================= --}}
{{-- COMPORTEMENT DU MENU (téléphone + page active de secours) --}}
{{-- ============================================================= --}}
<script>
document.addEventListener("DOMContentLoaded", () => {

    const sidebar    = document.getElementById("admin-sidebar");
    const menuButton = document.getElementById("admin-sidebar-toggle");
    const backdrop   = document.getElementById("admin-sidebar-backdrop");

    const ouvrirMenu = () => {
        sidebar?.classList.remove("-translate-x-full");
        sidebar?.classList.add("translate-x-0");
        backdrop?.classList.remove("hidden");
        menuButton?.setAttribute("aria-expanded", "true");
        menuButton?.setAttribute("aria-label", "Fermer le menu de navigation");
        if (window.lucide) window.lucide.createIcons();
    };

    const fermerMenu = () => {
        sidebar?.classList.add("-translate-x-full");
        sidebar?.classList.remove("translate-x-0");
        backdrop?.classList.add("hidden");
        menuButton?.setAttribute("aria-expanded", "false");
        menuButton?.setAttribute("aria-label", "Ouvrir le menu de navigation");
    };

    menuButton?.addEventListener("click", () => {
        const ouvert = menuButton.getAttribute("aria-expanded") === "true";
        ouvert ? fermerMenu() : ouvrirMenu();
    });

    backdrop?.addEventListener("click", fermerMenu);

    // Fermer le panneau après le choix d'une rubrique sur téléphone.
    sidebar?.querySelectorAll("#admin-sidebar-nav a.nav-link").forEach(link => {
        link.addEventListener("click", () => {
            if (window.innerWidth < 768) fermerMenu();
        });
    });

    // Si la fenêtre repasse en mode ordinateur, rétablir l'affichage normal.
    window.addEventListener("resize", () => {
        if (window.innerWidth >= 768) {
            sidebar?.classList.remove("-translate-x-full", "translate-x-0");
            backdrop?.classList.add("hidden");
            menuButton?.setAttribute("aria-expanded", "false");
        } else if (menuButton?.getAttribute("aria-expanded") !== "true") {
            sidebar?.classList.add("-translate-x-full");
        }
    });

    /*
    |--------------------------------------------------------------------------
    | PAGE ACTIVE : le serveur marque déjà le bon lien (aria-current).
    | Ce code ne sert que de secours, pour une page qui n'a pas de lien
    | correspondant (ex. : une URL qui ne suit pas les noms de routes).
    |--------------------------------------------------------------------------
    */
    const navLinks = document.querySelectorAll("#admin-sidebar-nav a.nav-link");
    const dejaActif = document.querySelector('#admin-sidebar-nav a[aria-current="page"]');

    if (!dejaActif) {
        const currentPath = window.location.pathname;
        let meilleur = null;
        let longueur = 0;

        navLinks.forEach(link => {
            const linkPath = new URL(link.href, window.location.origin).pathname;

            const correspond =
                currentPath === linkPath ||
                (linkPath !== "/" && currentPath.startsWith(linkPath + "/"));

            // On garde le lien le plus précis (le chemin le plus long)
            if (correspond && linkPath.length > longueur) {
                meilleur = link;
                longueur = linkPath.length;
            }
        });

        if (meilleur) {
            meilleur.classList.remove("text-emerald-100/70", "hover:bg-forest-700/50");
            meilleur.classList.add("bg-forest-700", "text-white", "shadow-md", "font-semibold");
        }
    }

    if (window.lucide) window.lucide.createIcons();
});
</script>
