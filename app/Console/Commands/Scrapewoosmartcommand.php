<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SousCategorie;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Scrape un site WooCommerce (ex: Asmama Maroc) en parcourant récursivement
 * les catégories, puis range chaque produit dans `sous_categories`
 * grâce à une taxonomie stricte à priorités (smartClassify).
 *
 * Dépendances :
 *   composer require symfony/dom-crawler symfony/css-selector
 *   php artisan storage:link
 *
 * Usage :
 *   php artisan scrape:woo-smart
 *   php artisan scrape:woo-smart https://asmamamaroc.com/categorie-produit/eclairage/ --limit=20
 *   php artisan scrape:woo-smart --dry-run --no-images
 */
class ScrapeWooSmartCommand extends Command
{
    protected $signature = 'scrape:woo-smart
        {url=https://asmamamaroc.com/categorie-produit/cables-electriques-et-cheminement-de-cables/ : URL de la catégorie de départ}
        {--limit=0 : Nombre max de produits à traiter (0 = illimité)}
        {--delay=400 : Pause entre deux requêtes, en millisecondes}
        {--max-depth=6 : Profondeur max de récursion des sous-catégories}
        {--dry-run : Affiche le classement sans écrire en base ni télécharger d\'images}
        {--no-images : Ne télécharge pas les images}
        {--use-description : Inclut la description dans le texte analysé par la classification}
        {--insecure : Désactive la vérification SSL (déconseillé)}';

    protected $description = 'Scrape un site WooCommerce et range les produits dans sous_categories (classification stricte par priorités)';

    // ──────────────────────────────────────────────────────────────
    // Noms EXACTS des sous-catégories présentes dans la table `sous_categories`
    // ──────────────────────────────────────────────────────────────
    private const SC_ACCESSOIRES = 'Accessoires de Pose, Fixation et Raccordement';
    private const SC_CABLES      = 'Câbles et Conducteurs';
    private const SC_TRANSFOS    = 'Transformateurs';
    private const SC_APPAREILLAGE = 'Appareillage et Accessoires Domestiques / Tertiaires';
    private const SC_HT_MT       = 'Matériel HT (Haute Tension) / MT (Moyenne Tension)';
    private const SC_RESEAUX     = 'Réseaux & Télécoms';
    private const SC_BT          = 'Matériel BT (Basse Tension)';

    /** @var array<string, SousCategorie> nom => modèle */
    private array $sousCategories = [];

    /** @var array<string, bool> URLs de catégories déjà visitées */
    private array $visitedCategories = [];

    /** @var array<string, bool> URLs de produits déjà traités */
    private array $seenProducts = [];

    /** @var array<string, int> Statistiques par sous-catégorie */
    private array $stats = [];

    private int $processed = 0;
    private int $failed = 0;
    private int $limit = 0;
    private int $delayMs = 400;
    private int $maxDepth = 6;
    private bool $dryRun = false;
    private bool $withImages = true;
    private bool $useDescription = false;
    private bool $insecure = false;
    private string $baseHost = '';
    private array $columnCache = [];

    /** @var array<string, string> URL produit => URL image vue sur la page de liste */
    private array $listingImages = [];

    // ══════════════════════════════════════════════════════════════
    // POINT D'ENTRÉE
    // ══════════════════════════════════════════════════════════════
    public function handle(): int
    {
        $startUrl = $this->normalizeCategoryUrl((string) $this->argument('url'));

        $this->limit          = (int) $this->option('limit');
        $this->delayMs        = max(0, (int) $this->option('delay'));
        $this->maxDepth       = max(0, (int) $this->option('max-depth'));
        $this->dryRun         = (bool) $this->option('dry-run');
        $this->withImages     = ! $this->option('no-images');
        $this->useDescription = (bool) $this->option('use-description');
        $this->insecure       = (bool) $this->option('insecure');
        $this->baseHost       = (string) parse_url($startUrl, PHP_URL_HOST);

        $this->line('');
        $this->line('<fg=cyan;options=bold>══ SCRAPING DEEP — ' . $this->baseHost . ' ══</>');
        $this->line("<fg=cyan>Départ :</> {$startUrl}");
        if ($this->dryRun) {
            $this->line('<fg=yellow>Mode DRY-RUN : aucune écriture en base, aucun téléchargement.</>');
        }

        if (! $this->loadSousCategories()) {
            return self::FAILURE;
        }

        $this->crawlCategory($startUrl, 0);

        $this->printSummary();

        return self::SUCCESS;
    }

    // ══════════════════════════════════════════════════════════════
    // SOUS-CATÉGORIES (BDD)
    // ══════════════════════════════════════════════════════════════
    private function loadSousCategories(): bool
    {
        $required = [
            self::SC_ACCESSOIRES, self::SC_CABLES, self::SC_TRANSFOS, self::SC_APPAREILLAGE,
            self::SC_HT_MT, self::SC_RESEAUX, self::SC_BT,
        ];

        $missing = [];
        foreach ($required as $nom) {
            $model = SousCategorie::where('nom', $nom)->first();
            if ($model) {
                $this->sousCategories[$nom] = $model;
                $this->stats[$nom] = 0;
            } else {
                $missing[] = $nom;
            }
        }

        if ($missing) {
            $this->error('Sous-catégories introuvables dans la table `sous_categories` (colonne `nom`) :');
            foreach ($missing as $nom) {
                $this->line("  <fg=red>✗</> {$nom}");
            }
            $this->line('Vérifie l\'orthographe exacte (accents, majuscules, "/" et "&").');

            return false;
        }

        $this->line('<fg=green>✔ 7 sous-catégories chargées depuis la base.</>');

        return true;
    }

    // ══════════════════════════════════════════════════════════════
    // CRAWL RÉCURSIF DES CATÉGORIES
    // ══════════════════════════════════════════════════════════════
    private function crawlCategory(string $catUrl, int $depth): void
    {
        if ($this->limitReached() || isset($this->visitedCategories[$catUrl])) {
            return;
        }
        $this->visitedCategories[$catUrl] = true;

        $this->line('');
        $this->line(sprintf('<fg=magenta;options=bold>%s📂 Catégorie : %s</>', str_repeat('  ', $depth), $catUrl));

        $subCategoryUrls = [];
        $productUrls = [];

        // Parcours de la pagination WooCommerce
        $pageUrl = $catUrl;
        $guard = 0;
        while ($pageUrl && $guard++ < 50) {
            $crawler = $this->fetch($pageUrl);
            if (! $crawler) {
                break;
            }

            $scope = $this->mainScope($crawler);

            foreach ($this->extractLinks($scope, 'a[href*="/categorie-produit/"]') as $href) {
                if ($this->isDescendantCategory($catUrl, $href)) {
                    $subCategoryUrls[$href] = true;
                }
            }
            foreach ($this->extractLinks($scope, 'a[href*="/produit/"]') as $href) {
                $productUrls[$href] = true;
            }

            // Images vues sur la page de liste (servent de secours si la fiche produit n'en expose pas)
            $scope->filter('a[href*="/produit/"]')->each(function (Crawler $a) {
                $img = $a->filter('img');
                if ($img->count() === 0) {
                    return;
                }
                $href = (string) strtok($this->absoluteUrl(trim((string) $a->attr('href')), 'https://' . $this->baseHost . '/'), '#?');
                $src = $this->imgSrc($img->first(), $href);
                if ($src && ! isset($this->listingImages[$href])) {
                    $this->listingImages[$href] = $src;
                }
            });

            $pageUrl = $this->nextPageUrl($crawler);
        }

        // 1) D'abord les sous-catégories : le produit est ainsi rattaché à sa catégorie
        //    la plus précise ($catUrl), pas à la catégorie racine.
        if ($depth < $this->maxDepth) {
            foreach (array_keys($subCategoryUrls) as $subUrl) {
                $this->crawlCategory($subUrl, $depth + 1);
                if ($this->limitReached()) {
                    return;
                }
            }
        }

        // 2) Ensuite les produits listés directement sur cette page
        foreach (array_keys($productUrls) as $productUrl) {
            if ($this->limitReached()) {
                return;
            }
            if (isset($this->seenProducts[$productUrl])) {
                continue;
            }
            $this->seenProducts[$productUrl] = true;
            $this->processProduct($productUrl, $catUrl);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // TRAITEMENT D'UN PRODUIT
    // ══════════════════════════════════════════════════════════════
    private function processProduct(string $productUrl, string $catUrl): void
    {
        $crawler = $this->fetch($productUrl);
        if (! $crawler) {
            $this->failed++;
            $this->line("  <fg=red>✗ Page produit inaccessible :</> {$productUrl}");

            return;
        }

        // ── Titre ─────────────────────────────────────────────
        $productName = $this->firstText($crawler, ['h1.product_title', '.product_title', 'h1.entry-title', 'h1']);
        if ($productName === '') {
            $this->failed++;
            $this->line("  <fg=red>✗ Titre introuvable :</> {$productUrl}");

            return;
        }

        // ── Description ───────────────────────────────────────
        $description = $this->firstText($crawler, [
            '#tab-description',
            '.woocommerce-Tabs-panel--description',
            '.description',
            '.woocommerce-product-details__short-description',
        ]);

        // ── Classification ────────────────────────────────────
        $slug = (string) Str::of(parse_url($productUrl, PHP_URL_PATH))->trim('/')->afterLast('/');
        [$sousCategorieNom, $rule] = $this->smartClassify($productName, $slug, $catUrl, $description);
        $sousCategorie = $this->sousCategories[$sousCategorieNom];

        $this->line(sprintf(
            '  <fg=green>➔ Produit :</> <options=bold>%s</> <fg=gray>-></> <fg=yellow>Rangé dans :</> <fg=cyan;options=bold>%s</> <fg=gray>(Règle: %s)</>',
            $productName,
            $sousCategorieNom,
            $rule
        ));

        $this->stats[$sousCategorieNom]++;
        $this->processed++;

        if ($this->dryRun) {
            return;
        }

        // ── Image principale ──────────────────────────────────
        $imagePath = null;
        if ($this->withImages) {
            $imageUrl = $this->extractMainImageUrl($crawler, $productUrl, $this->listingImages[$productUrl] ?? null);
            if ($imageUrl) {
                $imagePath = $this->downloadImage($imageUrl, $productName, $sousCategorieNom);
                if ($imagePath) {
                    $this->line("    <fg=blue>🖼  Image :</> {$imagePath}");
                } else {
                    $this->line("    <fg=yellow>⚠ Image non téléchargée :</> {$imageUrl}");
                }
            } else {
                $this->line('    <fg=yellow>⚠ Aucune image trouvée</>');
            }
        }

        // ── Enregistrement (sans doublons) ────────────────────
        // Adapte les noms de colonnes ci-dessous à ta table `products` ;
        // les colonnes absentes sont ignorées automatiquement.
        $payload = ['sous_categorie_id' => $sousCategorie->id];
        $this->setIfColumn($payload, ['description'], $description);
        $this->setIfColumn($payload, ['slug'], Str::slug($productName));
        $this->setIfColumn($payload, ['source_url', 'url_source', 'url'], $productUrl);
        if ($imagePath) {
            $this->setIfColumn($payload, ['image', 'image_path', 'photo', 'image_url'], $imagePath);
        }

        try {
            Product::updateOrCreate(['name' => $productName], $payload);
        } catch (\Throwable $e) {
            $this->failed++;
            $this->line("    <fg=red>✗ Erreur BDD :</> " . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // CLASSIFICATION INTELLIGENTE (ordre de priorité strict)
    // ══════════════════════════════════════════════════════════════
    /**
     * @return array{0: string, 1: string} [nom sous-catégorie, nom de la règle]
     */
    private function smartClassify(string $title, string $slug, string $catUrl, string $description = ''): array
    {
        $catPath = (string) parse_url($catUrl, PHP_URL_PATH);

        // Texte analysé : titre + slug produit (+ description si --use-description).
        // La description est exclue par défaut : elle cite souvent "gaine", "pince"...
        // dans la fiche d'un simple câble, ce qui créerait des faux positifs.
        $text = $title . ' ' . $slug;
        if ($this->useDescription) {
            $text .= ' ' . $description;
        }
        $textN = $this->norm($text);

        // Chemin de catégorie SANS la catégorie racine (ex: "cables-electriques-et-cheminement-de-cables"),
        // sinon le mot "cheminement"/"cable" de l'URL parente polluerait toutes les règles.
        $segments = $this->categorySegments($catPath);
        $childSegments = count($segments) > 1 ? array_slice($segments, 1) : [];
        $childPathN = $this->norm(implode(' ', $childSegments));

        // ── PRIORITÉ 0 : Cheminement vs Câbles ────────────────
        $p0 = '/\b(cheminements?|chemins? de cables?|goulottes?|echelles? a cables?|tubes? pehd|regards?)\b/';
        if (preg_match($p0, $textN) || preg_match($p0, $childPathN)) {
            return [self::SC_ACCESSOIRES, 'P0 Cheminement'];
        }

        // ── PRIORITÉ 1 : Héritage direct de la catégorie source ($catUrl) ──
        $fullPath = strtolower($catPath);
        if (preg_match('#/[^/]*transformateurs?[^/]*/|/[^/]*transformateurs?[^/]*$#', $fullPath)) {
            return [self::SC_TRANSFOS, 'P1 Héritage URL (transformateurs)'];
        }
        if (str_contains($fullPath, 'telecom')) {
            return [self::SC_RESEAUX, 'P1 Héritage URL (telecom)'];
        }
        // "cables" : on cible les sous-catégories "cable-xxx" (cable-domestique, cable-aerien...)
        // et PAS la racine "cables-electriques-et-cheminement-de-cables", ni "raccordement-cable".
        if (preg_match('#/cable-[a-z0-9-]+(/|$)#', $fullPath)) {
            return [self::SC_CABLES, 'P1 Héritage URL (cables)'];
        }
        if (str_contains($fullPath, 'eclairage')) {
            return [self::SC_APPAREILLAGE, 'P1 Héritage URL (eclairage)'];
        }

        // ── PRIORITÉ 2 : Télécoms & Réseaux ───────────────────
        if (preg_match('/\b(fibres? optiques?|sfp|patch ?cords?|jarretieres? optiques?|switch(?:es)? reseaux?|routeurs?)\b/', $textN)) {
            return [self::SC_RESEAUX, 'P2 Télécoms & Réseaux'];
        }

        // ── PRIORITÉ 3 : Accessoires & Pose ───────────────────
        if (preg_match('/\b(pinces?|cosses?|embouts?|colliers?|gaines?|raccords?|manchons?|boites? de derivation)\b/', $textN)) {
            return [self::SC_ACCESSOIRES, 'P3 Accessoires & Pose'];
        }

        // ── PRIORITÉ 4 : Transformateurs (hors compteurs / kits vidéo KCV-KC) ──
        if (preg_match('/\b(transformateurs?|transfos?|tc)\b/', $textN)
            && ! preg_match('/\b(compteurs?|kcv|kc)\b/', $textN)) {
            return [self::SC_TRANSFOS, 'P4 Transformateurs'];
        }

        // ── PRIORITÉ 5 : Matériel HT / MT ─────────────────────
        if (preg_match('/\b(haute tension|moyenne tension|cellules?|sectionneurs?)\b/', $textN)) {
            return [self::SC_HT_MT, 'P5 Matériel HT/MT'];
        }

        // ── PRIORITÉ 6 : Câbles ───────────────────────────────
        if (preg_match('/\b(cables?|conducteurs?|u ?1000|h07 ?v|torsad\w*|cuivre nu)\b/', $textN)) {
            return [self::SC_CABLES, 'P6 Câbles'];
        }

        // ── PRIORITÉ 7 : Appareillage ─────────────────────────
        if (preg_match('/\b(prises? de courant|interrupteurs?|va et vient|douilles?|domotique|lustres?|spots?|hublots?)\b/', $textN)) {
            return [self::SC_APPAREILLAGE, 'P7 Appareillage'];
        }

        // ── FALLBACK ──────────────────────────────────────────
        return [self::SC_BT, 'Fallback (BT par défaut)'];
    }

    /** minuscule, sans accents, ponctuation/tirets => espaces */
    private function norm(string $value): string
    {
        $value = Str::lower(Str::ascii($value));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));
    }

    /** Segments situés après /categorie-produit/ */
    private function categorySegments(string $path): array
    {
        $path = trim($path, '/');
        $parts = $path === '' ? [] : explode('/', $path);
        $idx = array_search('categorie-produit', $parts, true);
        if ($idx !== false) {
            $parts = array_slice($parts, $idx + 1);
        }

        return array_values(array_filter($parts, fn ($p) => $p !== '' && $p !== 'page' && ! ctype_digit($p)));
    }

    // ══════════════════════════════════════════════════════════════
    // EXTRACTION IMAGE
    // ══════════════════════════════════════════════════════════════
    private function extractMainImageUrl(Crawler $crawler, string $pageUrl, ?string $listingFallback = null): ?string
    {
        // 1) Sélecteurs WooCommerce / thèmes courants
        $selectors = [
            '.woocommerce-product-gallery__image img',
            '.woocommerce-product-gallery img',
            '.product .images img',
            '.images img',
            '.product-image img',
            '.product-images img',
            '.single-product img.wp-post-image',
            'img.wp-post-image',
            'img.attachment-shop_single',
            'img.attachment-woocommerce_single',
            'img.attachment-full',
        ];
        foreach ($selectors as $selector) {
            $node = $crawler->filter($selector);
            if ($node->count() > 0 && ($src = $this->imgSrc($node->first(), $pageUrl))) {
                return $src;
            }
        }

        // 2) Balises meta (og:image / twitter:image)
        foreach (['meta[property="og:image"]', 'meta[name="twitter:image"]'] as $selector) {
            $meta = $crawler->filter($selector);
            if ($meta->count() > 0 && ($content = $meta->first()->attr('content'))) {
                return $this->absoluteUrl($content, $pageUrl);
            }
        }

        // 3) Première image de /uploads/ dans le contenu principal (hors logo, favicon, icônes)
        $found = null;
        $this->mainScope($crawler)->filter('img')->each(function (Crawler $img) use (&$found, $pageUrl) {
            if ($found) {
                return;
            }
            $src = $this->imgSrc($img, $pageUrl);
            if ($src && str_contains($src, '/uploads/')
                && ! preg_match('/logo|favicon|icon|cropped|footer|placeholder|avatar/i', $src)) {
                $found = $src;
            }
        });
        if ($found) {
            return $found;
        }

        // 4) Image vue sur la page de liste de la catégorie
        return $listingFallback;
    }

    /** Lit l'URL d'une <img> (lazy-load, HD d'abord) et la rend absolue */
    private function imgSrc(Crawler $img, string $base): ?string
    {
        foreach (['data-large_image', 'data-src', 'data-lazy-src', 'data-original', 'src'] as $attr) {
            $value = trim((string) $img->attr($attr));
            if ($value !== '' && ! str_starts_with($value, 'data:') && ! preg_match('/placeholder|lazy\.(png|gif|svg)/i', $value)) {
                return $this->absoluteUrl($value, $base);
            }
        }

        // Dernier recours : plus grande image du srcset
        $srcset = trim((string) $img->attr('srcset'));
        if ($srcset !== '') {
            $parts = array_map('trim', explode(',', $srcset));
            $last = trim(explode(' ', (string) end($parts))[0]);
            if ($last !== '' && ! str_starts_with($last, 'data:')) {
                return $this->absoluteUrl($last, $base);
            }
        }

        return null;
    }

    private function downloadImage(string $imageUrl, string $productName, string $sousCategorieNom): ?string
    {
        // Tente d'abord la version originale (sans suffixe WordPress -300x300), puis l'URL telle quelle
        $original = preg_replace('/-\d{2,4}x\d{2,4}(\.[a-zA-Z0-9]+)(\?.*)?$/', '$1', $imageUrl);
        $candidates = array_values(array_unique(array_filter([$original, $imageUrl])));

        foreach ($candidates as $candidate) {
            try {
                $response = $this->http()->get($candidate);
                if (! $response->successful() || $response->body() === '') {
                    continue;
                }

                $ext = strtolower(pathinfo((string) parse_url($candidate, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
                    $ext = 'jpg';
                }

                $dir = 'produits/' . Str::slug($sousCategorieNom);
                $file = Str::slug($productName) . '-' . substr(md5($candidate), 0, 6) . '.' . $ext;
                $path = $dir . '/' . $file;

                Storage::disk('public')->put($path, $response->body());
                $this->pause();

                return $path; // accessible via /storage/produits/...
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    // ══════════════════════════════════════════════════════════════
    // HTTP / DOM
    // ══════════════════════════════════════════════════════════════
    private function http(): PendingRequest
    {
        $request = Http::withHeaders([
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.5',
            'Accept'          => 'text/html,application/xhtml+xml,image/*,*/*;q=0.8',
        ])->timeout(30)->retry(3, 1500, throw: false);

        return $this->insecure ? $request->withoutVerifying() : $request;
    }

    private function fetch(string $url): ?Crawler
    {
        try {
            $response = $this->http()->get($url);
            $this->pause();

            if (! $response->successful()) {
                $this->line("  <fg=red>✗ HTTP {$response->status()} :</> {$url}");

                return null;
            }

            return new Crawler($response->body(), $url);
        } catch (\Throwable $e) {
            $this->line("  <fg=red>✗ Erreur réseau :</> {$url} (" . $e->getMessage() . ')');

            return null;
        }
    }

    /** Zone de contenu principale (évite le menu latéral qui liste TOUTES les catégories) */
    private function mainScope(Crawler $crawler): Crawler
    {
        foreach (['main', '#main', '.site-main', '#content', '.content-area'] as $selector) {
            $node = $crawler->filter($selector);
            if ($node->count() > 0) {
                return $node->first();
            }
        }

        return $crawler;
    }

    /** @return string[] */
    private function extractLinks(Crawler $scope, string $selector): array
    {
        $links = [];
        $scope->filter($selector)->each(function (Crawler $a) use (&$links) {
            $href = trim((string) $a->attr('href'));
            if ($href === '' || str_starts_with($href, '#')) {
                return;
            }
            $href = $this->absoluteUrl($href, 'https://' . $this->baseHost . '/');
            $href = strtok($href, '#?');
            if (parse_url($href, PHP_URL_HOST) === $this->baseHost) {
                $links[$href] = true;
            }
        });

        return array_keys($links);
    }

    private function isDescendantCategory(string $parentUrl, string $href): bool
    {
        $parent = $this->normalizeCategoryUrl($parentUrl);
        $child = $this->normalizeCategoryUrl($href);

        return $child !== $parent
            && str_starts_with($child, $parent)
            && ! str_contains($child, '/page/');
    }

    private function nextPageUrl(Crawler $crawler): ?string
    {
        $next = $crawler->filter('a.next.page-numbers, a.next, link[rel="next"]');
        if ($next->count() === 0) {
            return null;
        }
        $href = $next->first()->attr('href');

        return $href ? $this->absoluteUrl($href, 'https://' . $this->baseHost . '/') : null;
    }

    private function normalizeCategoryUrl(string $url): string
    {
        return rtrim(strtok(trim($url), '#?'), '/') . '/';
    }

    private function absoluteUrl(string $url, string $base): string
    {
        $url = trim($url);
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? $this->baseHost);
        if (str_starts_with($url, '/')) {
            return $origin . $url;
        }

        return rtrim($origin . dirname($parts['path'] ?? '/'), '/') . '/' . $url;
    }

    private function firstText(Crawler $crawler, array $selectors): string
    {
        foreach ($selectors as $selector) {
            $node = $crawler->filter($selector);
            if ($node->count() > 0) {
                $text = trim((string) preg_replace('/\s+/u', ' ', $node->first()->text('')));
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    // ══════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ══════════════════════════════════════════════════════════════
    private function setIfColumn(array &$payload, array $candidates, mixed $value): void
    {
        foreach ($candidates as $column) {
            $this->columnCache[$column] ??= Schema::hasColumn((new Product)->getTable(), $column);
            if ($this->columnCache[$column]) {
                $payload[$column] = $value;

                return;
            }
        }
    }

    private function limitReached(): bool
    {
        return $this->limit > 0 && $this->processed >= $this->limit;
    }

    private function pause(): void
    {
        if ($this->delayMs > 0) {
            usleep($this->delayMs * 1000);
        }
    }

    private function printSummary(): void
    {
        $this->line('');
        $this->line('<fg=cyan;options=bold>══ RÉSUMÉ ══</>');

        $rows = [];
        foreach ($this->stats as $nom => $count) {
            $rows[] = [$nom, $count];
        }
        $this->table(['Sous-catégorie', 'Produits'], $rows);

        $this->line("<fg=green>✔ Produits traités : {$this->processed}</>");
        if ($this->failed > 0) {
            $this->line("<fg=red>✗ Échecs : {$this->failed}</>");
        }
        if ($this->dryRun) {
            $this->line('<fg=yellow>DRY-RUN : rien n\'a été enregistré.</>');
        }
    }
}
