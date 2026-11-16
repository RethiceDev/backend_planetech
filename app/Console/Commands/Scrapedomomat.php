<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\SousCategorie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;
use Throwable;

/**
 * Scraping de la catégorie « Disjoncteurs différentiels » de domomat.com (PrestaShop).
 *
 * Installation : composer require symfony/dom-crawler symfony/css-selector
 * Test à blanc : php artisan scrape:domomat --dry-run
 * Import réel  : php artisan scrape:domomat
 */
class ScrapeDomomat extends Command
{
    protected $signature = 'scrape:domomat
        {--url=https://www.domomat.com/2062-disjoncteur-differentiel : URL de la catégorie}
        {--pages=10 : Nombre maximum de pages à parcourir}
        {--dir= : Dossier contenant des pages HTML enregistrées depuis le navigateur}
        {--dry-run : Affiche seulement, sans rien enregistrer}';

    protected $description = 'Scrape les disjoncteurs différentiels de domomat.com';

    // Classement : tout ce listing est un disjoncteur différentiel.
    private const CATEGORIE      = 'Électricité Générale';
    private const SOUS_CATEGORIE = 'Disjoncteurs différentiels';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    public function handle(): int
    {
        $baseUrl = $this->option('url');
        $max     = (int) $this->option('pages');
        $dry     = (bool) $this->option('dry-run');

        if ($this->option('dir')) {
            return $this->depuisDossier($this->option('dir'), $baseUrl, $dry);
        }

        $vus = [];
        $total = 0;

        for ($page = 1; $page <= $max; $page++) {
            $pageUrl = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $page;
            $this->info("== Page {$page} : {$pageUrl}");

            try {
                $res = Http::withHeaders([
                    'User-Agent'      => self::UA,
                    'Accept-Language' => 'fr-FR,fr;q=0.9',
                ])->timeout(30)->retry(2, 1500)->get($pageUrl);
            } catch (Throwable $e) {
                $this->error('Requête impossible : ' . $e->getMessage());
                break;
            }

            if (!$res->successful()) {
                $this->error('HTTP ' . $res->status());
                break;
            }

            $produits = $this->extraireProduits($res->body(), $pageUrl);

            if (count($produits) === 0) {
                // On garde la page pour pouvoir ajuster les sélecteurs.
                Storage::put("domomat-page-{$page}.html", $res->body());
                $this->warn("Aucun produit trouvé. HTML sauvegardé dans storage/app/private/domomat-page-{$page}.html (ou storage/app/).");
                break;
            }

            $nouveaux = 0;

            foreach ($produits as $p) {
                if (isset($vus[$p['url']])) {
                    continue;
                }
                $vus[$p['url']] = true;
                $nouveaux++;
                $total++;

                $this->traiterProduit($p, $dry);
            }

            // Page qui ne renvoie que des doublons = on a dépassé la dernière page.
            if ($nouveaux === 0) {
                break;
            }

            sleep(2); // politesse envers le site
        }

        $this->info("Terminé : {$total} produit(s) " . ($dry ? '(dry-run, rien enregistré).' : 'traité(s).'));

        return self::SUCCESS;
    }

    /**
     * Mode hors-ligne : lit des pages HTML enregistrées manuellement depuis le navigateur
     * (utile quand le site bloque les requêtes automatiques).
     */
    private function depuisDossier(string $dir, string $baseUrl, bool $dry): int
    {
        $dir = rtrim($dir, '/\\');

        if (!is_dir($dir)) {
            $this->error("Le dossier n'existe pas : {$dir}");
            $this->line('Créez-le, puis enregistrez-y les pages (Ctrl+S dans le navigateur).');

            return self::FAILURE;
        }

        // Tous les fichiers du dossier, quelle que soit l'extension (.html, .htm, .txt...).
        $fichiers = [];
        foreach (scandir($dir) as $nom) {
            $chemin = $dir . DIRECTORY_SEPARATOR . $nom;
            if (is_file($chemin)) {
                $fichiers[] = $chemin;
            }
        }
        sort($fichiers);

        if (count($fichiers) === 0) {
            $this->error("Le dossier est vide : {$dir}");

            return self::FAILURE;
        }

        $this->line(count($fichiers) . ' fichier(s) trouvé(s) : ' . implode(', ', array_map('basename', $fichiers)));

        $vus = [];
        $total = 0;

        foreach ($fichiers as $fichier) {
            $this->info('== Fichier : ' . basename($fichier));

            $contenu  = file_get_contents($fichier);

            // Page enregistrée depuis « view-source: » : le code est échappé (&lt;article...)
            // et entouré de balises d'affichage. On retire l'habillage pour retrouver le HTML d'origine.
            if (!str_contains($contenu, '<article') && str_contains($contenu, '&lt;article')) {
                $this->line('    (fichier issu de view-source : conversion automatique)');
                $contenu = html_entity_decode(strip_tags($contenu), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $produits = $this->extraireProduits($contenu, $baseUrl);

            if (count($produits) === 0) {
                // Diagnostic : que contient réellement le fichier ?
                preg_match('/<title>(.*?)<\/title>/is', $contenu, $t);
                $this->warn('Aucun produit lu dans ce fichier. Diagnostic :');
                $this->line('    taille du fichier       : ' . number_format(strlen($contenu) / 1024, 0, ',', ' ') . ' Ko');
                $this->line('    titre de la page        : ' . trim($t[1] ?? '(aucun)'));
                $this->line('    occurrences "productMiniature" : ' . substr_count($contenu, 'productMiniature'));
                $this->line('    occurrences "<article"  : ' . substr_count($contenu, '<article'));
                $this->line('    occurrences "&lt;article" : ' . substr_count($contenu, '&lt;article'));
            }

            foreach ($produits as $p) {
                if (isset($vus[$p['url']])) {
                    continue;
                }
                $vus[$p['url']] = true;
                $total++;
                $this->traiterProduit($p, $dry);
            }
        }

        $this->info("Terminé : {$total} produit(s) " . ($dry ? '(dry-run).' : 'traité(s).'));

        return self::SUCCESS;
    }

    private function traiterProduit(array $p, bool $dry): void
    {
        $this->line("• {$p['nom']}");
        $this->line('    marque / réf.  : ' . ($p['marque'] ?? '-') . ' / ' . ($p['reference'] ?? '-'));
        $this->line('    sous-catégorie : ' . self::CATEGORIE . ' > ' . self::SOUS_CATEGORIE);
        $this->line('    règle          : listing "disjoncteur différentiel" -> sous-catégorie fixe');
        $this->line('    image          : ' . ($p['image'] ?: 'non trouvée'));

        if (!$dry) {
            $this->enregistrer($p);
        }
    }

    /**
     * Extrait les produits d'une page de listing Domomat.
     * Structure repérée : article.productMiniature > a.productMiniature-link,
     * .productMiniature-img img, .productMiniature-manufacturer, .productMiniature-ref span.
     */
    private function extraireProduits(string $html, string $pageUrl): array
    {
        $crawler = new Crawler($html, $pageUrl);
        $noeuds  = $crawler->filter('article.productMiniature');

        $produits = [];

        $noeuds->each(function (Crawler $n) use (&$produits, $pageUrl) {
            $lien = $n->filter('a.productMiniature-link')->first();

            if ($lien->count() === 0) {
                return;
            }

            $img = $n->filter('.productMiniature-img img')->first();

            // Nom : l'attribut alt de l'image contient le libellé complet
            // (le titre affiché dans le listing est tronqué avec « ... »).
            $nom = '';
            if ($img->count() > 0) {
                $nom = trim(html_entity_decode($img->attr('alt') ?? '', ENT_QUOTES | ENT_HTML5));
            }
            if ($nom === '') {
                $titre = $n->filter('.productMiniature-title, .productMiniature-name, h2, h3')->first();
                $nom = $titre->count() > 0 ? trim(preg_replace('/\s+/u', ' ', $titre->text(''))) : '';
            }
            if ($nom === '') {
                $nom = trim($lien->attr('title') ?? '');
            }

            $marqueNode = $n->filter('.productMiniature-manufacturer')->first();
            $refNode    = $n->filter('.productMiniature-ref span')->first();
            $marque = $marqueNode->count() > 0 ? trim($marqueNode->text('')) : null;
            $ref    = $refNode->count() > 0 ? trim($refNode->text('')) : null;

            // Image : l'URL peut être relative (ex. /250010-large_default/i.webp).
            $image = null;
            if ($img->count() > 0) {
                foreach (['data-src', 'data-lazy-src', 'src'] as $attr) {
                    $v = $img->attr($attr);
                    if ($v && !str_starts_with($v, 'data:')) {
                        $image = str_starts_with($v, 'http') ? $v : UriResolver::resolve($v, $pageUrl);
                        break;
                    }
                }
            }

            $desc = array_filter([
                $marque ? "Marque : {$marque}" : null,
                $ref ? "Réf. : {$ref}" : null,
            ]);

            $produits[] = [
                'nom'         => $nom,
                'url'         => $lien->link()->getUri(),
                'image'       => $image,
                'marque'      => $marque,
                'reference'   => $ref,
                'description' => $desc ? implode(' | ', $desc) : null,
            ];
        });

        return array_values(array_filter($produits, fn ($p) => $p['nom'] !== ''));
    }

    /**
     * Enregistrement aligné sur scrape:123elec-securite-acces :
     * Category(nom) -> SousCategorie(category_id, nom) -> Product(sous_categorie_id, name, price, photo, description).
     */
    private function enregistrer(array $p): void
    {
        $categorie = Category::firstOrCreate(['nom' => self::CATEGORIE]);

        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $categorie->id,
            'nom'         => self::SOUS_CATEGORIE,
        ]);

        $produit = Product::where('sous_categorie_id', $sousCategorie->id)
            ->where('name', $p['nom'])
            ->first();

        // Déjà importé avec une photo : on ne refait rien.
        if ($produit && !empty($produit->photo)) {
            $this->line('    [=] Déjà présent');

            return;
        }

        $photo = $p['image'] ? $this->telechargerImage($p['image'], $p['nom']) : null;

        if ($produit) {
            $produit->update(['photo' => $photo ?? $produit->photo]);
            $this->line('    [🔄] Mis à jour');

            return;
        }

        Product::create([
            'sous_categorie_id' => $sousCategorie->id,
            'name'              => $p['nom'],
            'price'             => null, // pas de prix
            'photo'             => $photo,
            'description'       => $p['description'],
        ]);

        $this->line('    [✔] Inséré');
    }

    /**
     * Télécharge l'image dans storage/app/public/products et renvoie 'storage/products/xxx'
     * (même format que vos autres scrapers). Pensez à : php artisan storage:link
     */
    private function telechargerImage(string $url, string $nom): ?string
    {
        try {
            $res = Http::withHeaders([
                'User-Agent' => self::UA,
                'Referer'    => 'https://www.domomat.com/',
            ])->withOptions(['verify' => false])->timeout(30)->get($url);

            if (!$res->successful() || empty($res->body())) {
                return null;
            }

            $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
            $fichier = Str::slug(Str::limit($nom, 40, '')) . '-' . substr(md5($url), 0, 8) . '.' . $ext;

            Storage::disk('public')->put('products/' . $fichier, $res->body());

            return 'storage/products/' . $fichier;
        } catch (Throwable $e) {
            return null;
        }
    }
}
