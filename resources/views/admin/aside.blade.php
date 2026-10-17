@php
    $admin = Auth::guard('admin')->user();
@endphp

{{-- Bouton menu visible sur téléphone --}}
<button
    id="admin-sidebar-toggle"
    type="button"
    aria-label="Ouvrir le menu de navigation"
    aria-controls="admin-sidebar"
    aria-expanded="false"
    class="fixed left-4 top-4 z-[60] inline-flex h-11 w-11 items-center justify-center
           rounded-xl bg-forest-900 text-white shadow-lg transition hover:bg-forest-800
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
           md:static md:z-auto md:min-h-screen md:max-w-none md:translate-x-0
           md:shrink-0 md:overflow-visible md:transition-none"
>

    {{-- ========================================================= --}}
    {{-- PARTIE HAUTE --}}
    {{-- ========================================================= --}}

    <div>

        {{-- ========================================================= --}}
        {{-- LOGO / PROFIL ADMIN --}}
        {{-- ========================================================= --}}

        <a
            href="{{ route('admin.profil') }}"
            class="block p-6 transition-all duration-200 hover:bg-forest-800/60"
        >
            <div class="flex items-center gap-3">

                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="Logo PLANETECH"
                    class="h-16 w-auto object-contain"
                >

                <div class="min-w-0">
                    <h1 class="font-bold tracking-wide text-sm">
                        PLANETECH
                    </h1>

                    <p class="text-xs text-emerald-400/80 truncate">
                        {{ $admin->prenom }} {{ $admin->nom }}
                    </p>
                </div>

            </div>
        </a>


        {{-- ========================================================= --}}
        {{-- NAVIGATION --}}
        {{-- ========================================================= --}}

        <nav
            class="mt-4 px-3 space-y-1.5"
            id="admin-sidebar-nav"
        >

            {{-- VUE D'ENSEMBLE --}}
            <a
                href="/"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
            >
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>

                <span>Vue d'ensemble</span>
            </a>


            {{-- DEVIS --}}
            <a
                href="/devis"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
            >
                <i data-lucide="file-text" class="w-5 h-5"></i>

                <span>Devis</span>
            </a>


            {{-- CATÉGORIES --}}
            <a
                href="{{ route('categories.index') }}"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
            >
                <i data-lucide="tags" class="w-5 h-5"></i>

                <span>Catégories</span>
            </a>


            {{-- SOUS-CATÉGORIES --}}
            <a
                href="{{ route('sous-categories.index') }}"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
            >
                <i data-lucide="list-tree" class="w-5 h-5"></i>

                <span>Sous-catégories</span>
            </a>


            {{-- BOUTIQUE --}}
            <a
                href="{{ route('produits.index') }}"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
            >
                <i data-lucide="store" class="w-5 h-5"></i>

                <span>Boutique</span>
            </a>


            {{-- COMMANDES --}}
            <a
                href="/commande"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
             >
                <i data-lucide="shopping-cart" class="w-5 h-5"></i>

                <span>Bons de Commandes</span>
            </a>


             {{-- COMMANDES --}}
            <a
                href="{{ route('realisations.index') }}"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
             >
                <i data-lucide="image" class="w-5 h-5"></i>

                <span>Realisation</span>
            </a>


        <a
            href="{{ route('admin.decharges.index') }}"
            class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl font-medium text-sm transition-all duration-200 {{ request()->routeIs('admin.decharges.*') ? 'bg-forest-700 text-white shadow-sm' : 'text-emerald-100/70 hover:bg-forest-700/50 hover:text-white' }}"
            >
            <i data-lucide="file-check-2" class="w-5 h-5"></i>
            <span>Décharges</span>
        </a>

            <a
                href="/admin/procurations"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                    text-emerald-100/70 hover:bg-forest-700/50
                    hover:text-white font-medium text-sm transition-all duration-200"
             >
                <i data-lucide="file-signature" class="w-5 h-5"></i>
                <span>Procurations</span>
            </a>



            {{-- ===================================================== --}}
            {{-- AUTRES RUBRIQUES --}}
            {{-- ===================================================== --}}

            {{--
            <a
                href="{{ route('domaines.index') }}"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
            >
                <i data-lucide="wrench" class="w-5 h-5"></i>
                <span>Domaines</span>
            </a>


            <a
                href="{{ route('equipes.index') }}"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
            >
                <i data-lucide="users" class="w-5 h-5"></i>
                <span>Équipe</span>
            </a>


            <a
                href="{{ route('partenaires.index') }}"
                class="nav-link flex items-center gap-3 px-4 py-3 rounded-2xl
                       text-emerald-100/70 hover:bg-forest-700/50
                       hover:text-white font-medium text-sm transition-all duration-200"
            >
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
            href="https://planetechsarlbenin.com"
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
        <form
            action="{{ route('admin.logout') }}"
            method="POST"
            class="w-full"
        >
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
{{-- GESTION DU MENU ACTIF --}}
{{-- ============================================================= --}}

<script>
document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.getElementById("admin-sidebar");
    const menuButton = document.getElementById("admin-sidebar-toggle");
    const backdrop = document.getElementById("admin-sidebar-backdrop");

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
    sidebar?.querySelectorAll("#admin-sidebar-nav .nav-link").forEach(link => {
        link.addEventListener("click", () => {
            if (window.innerWidth < 768) fermerMenu();
        });
    });

    // Si la fenêtre repasse en mode ordinateur, rétablir l'affichage normal.
    window.addEventListener("resize", () => {
        if (window.innerWidth >= 768) {
            sidebar?.classList.remove("-translate-x-full");
            sidebar?.classList.remove("translate-x-0");
            backdrop?.classList.add("hidden");
            menuButton?.setAttribute("aria-expanded", "false");
        } else if (menuButton?.getAttribute("aria-expanded") !== "true") {
            sidebar?.classList.add("-translate-x-full");
        }
    });

    const currentPath = window.location.pathname;

    const navLinks = document.querySelectorAll(
        "#admin-sidebar-nav .nav-link"
    );

    navLinks.forEach(link => {

        const linkPath = new URL(
            link.href,
            window.location.origin
        ).pathname;

        /*
        |--------------------------------------------------------------------------
        | PAGE ACTIVE
        |--------------------------------------------------------------------------
        */

        if (
            currentPath === linkPath ||
            (
                linkPath !== "/" &&
                currentPath.startsWith(linkPath)
            )
        ) {

            link.classList.remove(
                "text-emerald-100/70",
                "hover:bg-forest-700/50"
            );

            link.classList.add(
                "bg-forest-700",
                "text-white",
                "shadow-md",
                "font-semibold"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | EFFET AU CLIC
        |--------------------------------------------------------------------------
        */

        link.addEventListener("click", () => {

            navLinks.forEach(item => {

                item.classList.remove(
                    "bg-forest-700",
                    "text-white",
                    "shadow-md",
                    "font-semibold"
                );

                item.classList.add(
                    "text-emerald-100/70",
                    "hover:bg-forest-700/50"
                );

            });

            link.classList.remove(
                "text-emerald-100/70",
                "hover:bg-forest-700/50"
            );

            link.classList.add(
                "bg-forest-700",
                "text-white",
                "shadow-md",
                "font-semibold"
            );

        });

    });

    if (window.lucide) window.lucide.createIcons();
});
</script>
