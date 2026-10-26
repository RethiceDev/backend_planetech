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

class ScrapeSoumari extends Command
{
    protected $signature = 'soumari:scrape
                            {--url= : URL de la catégorie à importer (par défaut : Support TV)}
                            {--max-pages=10 : Nombre maximum de pages}
                            {--dry-run : Affiche ce qui serait importé, sans rien écrire}';

    protected $description = 'Importe les produits de Soumari (catégorie Support TV) avec photos, classés automatiquement, prix à NULL';

    private const URL_PAR_DEFAUT = 'https://www.soumari.com/categorie-produit/television/support-tv/';

    private int $importes = 0;
    private int $sansPhoto = 0;
    private int $existants = 0;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $base = rtrim($this->option('url') ?: self::URL_PAR_DEFAUT, '/') . '/';
        $maxPages = max(1, (int) $this->option('max-pages'));

        $this->info($dry ? 'MODE TEST : rien ne sera écrit.' : 'Import en cours...');

        for ($page = 1; $page <= $maxPages; $page++) {
            $url = $page === 1 ? $base : $base . 'page/' . $page . '/';
            $this->info('--------------------------------------------------');
            $this->info("Page {$page} : {$url}");

            $reponse = $this->requete($url);

            if (!$reponse || $reponse->status() === 404 || $reponse->failed()) {
                $this->info('Plus de page à lire. Fin.');
                break;
            }

            $crawler = new Crawler($reponse->body());
            $produits = $crawler->filter('li.product');

            if ($produits->count() === 0) {
                $this->info('Aucun produit trouvé. Fin.');
                break;
            }

            $produits->each(function (Crawler $node) use ($dry) {
                try {
                    $this->traiter($node, $dry);
                } catch (\Throwable $e) {
                    $this->warn('    [ERREUR] ' . $e->getMessage());
                }
            });

            sleep(2);
        }

        $this->newLine();
        $this->info("Terminé : {$this->importes} " . ($dry ? 'à importer' : 'importés')
            . ", {$this->existants} déjà présents, {$this->sansPhoto} ignorés (sans photo).");

        return self::SUCCESS;
    }

    private function traiter(Crawler $node, bool $dry): void
    {
        $lien = $node->filter('h2 a');
        if ($lien->count() === 0) {
            return;
        }

        $nom = Str::squish(html_entity_decode($lien->first()->text()));
        $urlProduit = $lien->first()->attr('href');

        if ($nom === '' || !$urlProduit) {
            return;
        }

        // Déjà en base : on n'importe pas deux fois
        if (Product::where('name', $nom)->exists()) {
            $this->existants++;
            $this->line("    [DÉJÀ PRÉSENT] {$nom}");
            return;
        }

        // Photo de la liste, sinon photo de la page produit
        $imagesCandidates = [];
        $imageListe = $this->imageDepuisListe($node);
        if ($imageListe) {
            $imagesCandidates[] = $this->versionOriginale($imageListe);
            $imagesCandidates[] = $imageListe;
        }

        $description = '';
        $page = $this->requete($urlProduit);

        if ($page && $page->successful()) {
            $crawlerProduit = new Crawler($page->body());

            $descNode = $crawlerProduit->filter(
                '.woocommerce-product-details__short-description, #tab-description, .woocommerce-Tabs-panel--description'
            );
            if ($descNode->count() > 0) {
                $description = $this->nettoyerDescription($descNode->first()->text());
            }

            $galerie = $crawlerProduit->filter('.woocommerce-product-gallery__image img');
            if ($galerie->count() > 0) {
                foreach (['data-large_image', 'data-src', 'src'] as $attr) {
                    $valeur = $galerie->first()->attr($attr);
                    if ($valeur && !str_starts_with($valeur, 'data:')) {
                        $imagesCandidates[] = $valeur;
                        break;
                    }
                }
            }

            usleep(800000);
        }

        if (empty($imagesCandidates)) {
            $this->sansPhoto++;
            $this->line("    [IGNORÉ - pas de photo] {$nom}");
            return;
        }

        [$nomCategorie, $nomSousCategorie] = $this->classer($nom);

        if ($dry) {
            $this->importes++;
            $this->line("    [À IMPORTER] {$nom} -> [{$nomCategorie} > {$nomSousCategorie}]");
            return;
        }

        $chemin = $this->telechargerImage($imagesCandidates, $nom);

        if (!$chemin) {
            $this->sansPhoto++;
            $this->line("    [IGNORÉ - échec téléchargement photo] {$nom}");
            return;
        }

        $categorie = Category::firstOrCreate(['nom' => $nomCategorie]);

        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $categorie->id,
            'nom'         => $nomSousCategorie,
        ]);

        Product::firstOrCreate(
            [
                'name'              => $nom,
                'sous_categorie_id' => $sousCategorie->id,
            ],
            [
                'description' => $description !== '' ? $description : 'Description à compléter.',
                'photo'       => $chemin,
                'price'       => null, // Strictement NULL
                'stock'       => true,
            ]
        );

        $this->importes++;
        $this->line("    [SUCCÈS] {$nom} -> [{$nomCategorie} > {$nomSousCategorie}]");
    }

    /**
     * Classement intelligent : [catégorie principale, sous-catégorie].
     */
    private function classer(string $nom): array
    {
        $t = Str::lower(Str::ascii($nom));

        if (str_contains($t, 'micro onde')) {
            return ['Électricité', 'Supports Muraux Électroménager'];
        }
        if (str_contains($t, 'moniteur')) {
            return ['Informatique', 'Supports de Moniteur'];
        }
        if (str_contains($t, 'decodeur')) {
            return ['Télécoms & Réseau', 'Supports Décodeur'];
        }
        if (str_contains($t, 'chariot') || str_contains($t, 'mobile')) {
            return ['Informatique', 'Supports TV Mobiles'];
        }
        if (str_contains($t, 'plafond')) {
            return ['Informatique', 'Supports TV Plafond'];
        }
        if (str_contains($t, 'sur pied')) {
            return ['Informatique', 'Supports TV sur Pied'];
        }

        return ['Informatique', 'Supports TV Muraux'];
    }

    private function imageDepuisListe(Crawler $node): ?string
    {
        $img = $node->filter('img');
        if ($img->count() === 0) {
            return null;
        }

        foreach (['data-lazy-src', 'data-src', 'data-original', 'src'] as $attr) {
            $valeur = $img->first()->attr($attr);
            if ($valeur && !str_starts_with($valeur, 'data:')) {
                return $valeur;
            }
        }

        return null;
    }

    /**
     * Retire le suffixe de miniature (-300x300) pour obtenir l'image en pleine taille.
     */
    private function versionOriginale(string $url): string
    {
        return preg_replace('/-\d{2,4}x\d{2,4}(\.(?:jpe?g|png|webp))$/i', '$1', $url);
    }

    private function telechargerImage(array $urls, string $nom): ?string
    {
        foreach (array_unique(array_filter($urls)) as $url) {
            try {
                $reponse = Http::withHeaders($this->entetes())->timeout(30)->get($url);

                if (
                    $reponse->successful()
                    && str_starts_with((string) $reponse->header('Content-Type'), 'image/')
                    && strlen($reponse->body()) > 1000
                ) {
                    $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
                    $chemin = 'products/' . Str::slug($nom) . '-' . Str::random(6) . '.' . $ext;

                    Storage::disk('public')->put($chemin, $reponse->body());

                    return $chemin;
                }
            } catch (\Throwable $e) {
                // on essaie l'image suivante
            }
        }

        return null;
    }

    /**
     * Retire les phrases parasites : mentions de la source, livraison, texte d'IA.
     */
    private function nettoyerDescription(string $texte): string
    {
        $texte = preg_replace('/ChatGPT a dit\s*:?/iu', '', $texte);
        $texte = Str::squish((string) $texte);

        $phrases = preg_split('/(?<=[.!?])\s+/u', $texte) ?: [];
        $phrases = array_filter($phrases, fn ($p) => !preg_match('/soumari|livr|achetez|dakar|s[eé]n[eé]gal/iu', $p));

        return Str::limit(trim(implode(' ', $phrases)), 1500, '');
    }

    private function requete(string $url)
    {
        try {
            return Http::withHeaders($this->entetes())->timeout(30)->retry(3, 1000, null, false)->get($url);
        } catch (\Throwable $e) {
            $this->warn('    Connexion impossible : ' . $e->getMessage());
            return null;
        }
    }

    private function entetes(): array
    {
        return [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36',
            'Accept-Language' => 'fr-FR,fr;q=0.9',
        ];
    }
}
