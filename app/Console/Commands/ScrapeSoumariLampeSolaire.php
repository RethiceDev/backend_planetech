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
use Throwable;

class ScrapeSoumariLampeSolaire extends Command
{
    protected $signature = 'soumari:scrape
        {--url= : URL de la catégorie à importer}
        {--max-pages=20 : Nombre maximum de pages}
        {--dry-run : Simuler sans modifier la base ni les fichiers}
        {--force-images : Réparer les photos manquantes des produits existants}';

    protected $description =
        'Importe les produits Soumari et répare leurs images dans Laravel.';

    private const URL_PAR_DEFAUT =
        'https://www.soumari.com/categorie-produit/energie-solaire/lampe-solaire/';

    private int $importes = 0;
    private int $photosReparees = 0;
    private int $existants = 0;
    private int $sansPhoto = 0;
    private int $erreurs = 0;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $forceImages = (bool) $this->option('force-images');

        $base = rtrim(
            $this->option('url') ?: self::URL_PAR_DEFAUT,
            '/'
        ) . '/';

        $maxPages = max(1, (int) $this->option('max-pages'));

        $this->info('==============================================');
        $this->info(' PLANETECH SARL - IMPORT SOUMARI');
        $this->info('==============================================');

        if ($dry) {
            $this->warn('MODE TEST : aucune modification ne sera effectuée.');
        }

        if ($forceImages) {
            $this->warn('MODE RÉPARATION DES PHOTOS ACTIVÉ.');
        }

        for ($page = 1; $page <= $maxPages; $page++) {
            $urlPage = $page === 1
                ? $base
                : $base . 'page/' . $page . '/';

            $this->newLine();
            $this->info("Lecture de la page {$page} : {$urlPage}");

            $reponse = $this->requete($urlPage);

            if (!$reponse || !$reponse->successful()) {
                $this->warn("Impossible de lire la page {$page}. Arrêt.");
                break;
            }

            $crawler = new Crawler($reponse->body());
            $produits = $crawler->filter('li.product');

            if ($produits->count() === 0) {
                $this->info('Aucun produit supplémentaire trouvé.');
                break;
            }

            $produits->each(function (Crawler $node) use (
                $dry,
                $forceImages
            ) {
                try {
                    $this->traiter($node, $dry, $forceImages);
                } catch (Throwable $e) {
                    $this->erreurs++;

                    $this->error(
                        '[ERREUR PRODUIT] ' . $e->getMessage()
                    );
                }
            });

            sleep(1);
        }

        $this->newLine();
        $this->info('==============================================');
        $this->info(' RÉSUMÉ DE L’OPÉRATION');
        $this->info('==============================================');

        $this->line("Produits importés : {$this->importes}");
        $this->line("Photos réparées : {$this->photosReparees}");
        $this->line("Produits déjà valides : {$this->existants}");
        $this->line("Photos introuvables : {$this->sansPhoto}");
        $this->line("Erreurs : {$this->erreurs}");

        return self::SUCCESS;
    }

    private function traiter(
        Crawler $node,
        bool $dry,
        bool $forceImages
    ): void {
        $lien = $node->filter(
            'h2 a, .woocommerce-loop-product__link'
        );

        if ($lien->count() === 0) {
            return;
        }

        $nom = Str::squish(
            html_entity_decode(
                $lien->first()->text(),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );

        $urlProduit = $lien->first()->attr('href');

        if ($nom === '' || !$urlProduit) {
            return;
        }

        $urlProduit = $this->normaliserUrl($urlProduit);

        $produitExistant = Product::where('name', $nom)->first();

        /*
         * Ne pas télécharger à nouveau une photo locale valide.
         */
        if (
            $produitExistant
            && $this->photoLocaleValide($produitExistant->photo)
        ) {
            $this->existants++;

            $this->line("[DÉJÀ VALIDE] {$nom}");

            return;
        }

        /*
         * Récupération des URL des images de la liste.
         */
        $imagesCandidates = $this->imagesDepuisListe($node);

        $description = 'Produit importé depuis Soumari';

        /*
         * Récupération des images de la page détaillée.
         */
        $pageProduit = $this->requete($urlProduit);

        if ($pageProduit && $pageProduit->successful()) {
            $crawlerProduit = new Crawler($pageProduit->body());

            $descNode = $crawlerProduit->filter(
                '.woocommerce-product-details__short-description, '
                . '#tab-description, '
                . '.woocommerce-Tabs-panel--description, '
                . '.description'
            );

            if ($descNode->count() > 0) {
                $description = $this->nettoyerDescription(
                    $descNode->first()->text()
                );
            }

            $galerie = $crawlerProduit->filter(
                '.woocommerce-product-gallery__image img, '
                . '.woocommerce-product-gallery img, '
                . 'img.wp-post-image'
            );

            foreach ($galerie as $image) {
                $img = new Crawler($image);

                foreach ([
                    'data-large_image',
                    'data-src',
                    'data-lazy-src',
                    'data-original',
                    'src',
                ] as $attribut) {
                    $valeur = trim(
                        (string) $img->attr($attribut)
                    );

                    if (
                        $valeur === ''
                        || str_starts_with($valeur, 'data:')
                        || str_starts_with(strtolower($valeur), 'blob:')
                    ) {
                        continue;
                    }

                    $imagesCandidates[] = $valeur;

                    break;
                }

                $srcset = trim((string) $img->attr('srcset'));

                if ($srcset !== '') {
                    foreach (explode(',', $srcset) as $source) {
                        $parties = preg_split(
                            '/\s+/',
                            trim($source)
                        );

                        if (!empty($parties[0])) {
                            $imagesCandidates[] = $parties[0];
                        }
                    }
                }
            }
        }

        /*
         * Essayer les URL originales et les miniatures.
         */
        $candidatsFinaux = [];

        foreach ($imagesCandidates as $imageUrl) {
            $imageUrl = $this->normaliserUrl($imageUrl);

            if ($imageUrl === '') {
                continue;
            }

            $candidatsFinaux[] = $this->versionOriginale($imageUrl);
            $candidatsFinaux[] = $imageUrl;
        }

        $candidatsFinaux = array_values(
            array_unique(array_filter($candidatsFinaux))
        );

        if (empty($candidatsFinaux)) {
            $this->sansPhoto++;

            $this->warn("[SANS PHOTO SOURCE] {$nom}");

            return;
        }

        if ($dry) {
            $this->line(
                '[TEST] ' . $nom
                . ' : ' . count($candidatsFinaux)
                . ' URL(s) image trouvée(s).'
            );

            return;
        }

        /*
         * Télécharger et vérifier la photo.
         */
        $ancienChemin = $produitExistant?->photo;

        $chemin = $this->telechargerImage(
            $candidatsFinaux,
            $nom
        );

        if (!$chemin) {
            $this->sansPhoto++;

            $this->warn("[ÉCHEC PHOTO] {$nom}");

            return;
        }

        /*
         * Réparer un produit déjà enregistré.
         */
        if ($produitExistant) {
            $produitExistant->photo = $chemin;
            $produitExistant->save();

            if (
                $ancienChemin
                && $ancienChemin !== $chemin
                && Storage::disk('public')->exists($ancienChemin)
            ) {
                Storage::disk('public')->delete($ancienChemin);
            }

            $this->photosReparees++;

            $this->info("[PHOTO RÉPARÉE] {$nom}");

            return;
        }

        /*
         * Classer le nouveau produit.
         */
        [$nomCategorie, $nomSousCategorie] = $this->classer($nom);

        $categorie = Category::firstOrCreate([
            'nom' => $nomCategorie,
        ]);

        $sousCategorie = SousCategorie::firstOrCreate([
            'category_id' => $categorie->id,
            'nom' => $nomSousCategorie,
        ]);

        /*
         * Enregistrer le produit avec un prix NULL.
         */
        try {
            Product::create([
                'name' => $nom,
                'sous_categorie_id' => $sousCategorie->id,
                'description' => $description !== ''
                    ? $description
                    : 'Description à compléter.',
                'photo' => $chemin,
                'price' => null,
                'stock' => true,
            ]);

            $this->importes++;

            $this->info("[IMPORTÉ] {$nom}");

        } catch (Throwable $e) {
            Storage::disk('public')->delete($chemin);

            throw $e;
        }
    }

    /**
     * Récupère plusieurs URL potentielles dans les images de la liste.
     */
    private function imagesDepuisListe(Crawler $node): array
    {
        $resultat = [];

        $images = $node->filter('img');

        foreach ($images as $image) {
            $img = new Crawler($image);

            foreach ([
                'data-large_image',
                'data-lazy-src',
                'data-src',
                'data-original',
                'src',
            ] as $attribut) {
                $valeur = trim((string) $img->attr($attribut));

                if (
                    $valeur !== ''
                    && !str_starts_with($valeur, 'data:')
                    && !str_starts_with(strtolower($valeur), 'blob:')
                ) {
                    $resultat[] = $valeur;
                }
            }

            $srcset = trim((string) $img->attr('srcset'));

            if ($srcset !== '') {
                foreach (explode(',', $srcset) as $source) {
                    $parties = preg_split(
                        '/\s+/',
                        trim($source)
                    );

                    if (!empty($parties[0])) {
                        $resultat[] = $parties[0];
                    }
                }
            }
        }

        return array_values(array_unique($resultat));
    }

    /**
     * Convertit les URL relatives en URL absolues.
     */
    private function normaliserUrl(string $url): string
    {
        $url = trim(
            html_entity_decode(
                $url,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            return 'https://www.soumari.com' . $url;
        }

        if (!preg_match('#^https?://#i', $url)) {
            return 'https://www.soumari.com/'
                . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Retire les dimensions des miniatures WordPress.
     */
    private function versionOriginale(string $url): string
    {
        return preg_replace(
            '/-\d{2,4}x\d{2,4}(?=\.(?:jpe?g|png|webp|gif|avif)(?:[?#]|$))/i',
            '',
            $url
        ) ?? $url;
    }

    /**
     * Télécharge et enregistre une image valide.
     */
    private function telechargerImage(
        array $urls,
        string $nom
    ): ?string {
        foreach (array_unique($urls) as $url) {
            try {
                $url = $this->normaliserUrl($url);

                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $reponse = Http::withHeaders($this->entetes())
                    ->connectTimeout(10)
                    ->timeout(30)
                    ->retry(2, 1000)
                    ->get($url);

                if (!$reponse->successful()) {
                    $this->line(
                        '[HTTP ' . $reponse->status() . '] ' . $url
                    );

                    continue;
                }

                $contenu = $reponse->body();

                if (strlen($contenu) < 500) {
                    continue;
                }

                $infos = @getimagesizefromstring($contenu);

                if ($infos === false) {
                    $this->line("[CONTENU INVALIDE] {$url}");

                    continue;
                }

                $type = strtolower($infos['mime'] ?? '');

                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                    'image/avif' => 'avif',
                    'image/bmp' => 'bmp',
                ];

                if (!isset($extensions[$type])) {
                    continue;
                }

                $extension = $extensions[$type];

                $chemin = 'products/'
                    . Str::slug($nom)
                    . '-'
                    . Str::random(10)
                    . '.'
                    . $extension;

                $disque = Storage::disk('public');

                $disque->makeDirectory('products');

                $ecriture = $disque->put($chemin, $contenu);

                if (!$ecriture || !$disque->exists($chemin)) {
                    $this->warn("[ÉCHEC ENREGISTREMENT] {$chemin}");

                    continue;
                }

                $fichier = $disque->get($chemin);

                if (@getimagesizefromstring($fichier) === false) {
                    $disque->delete($chemin);

                    continue;
                }

                $this->line("[IMAGE OK] {$chemin}");

                return $chemin;

            } catch (Throwable $e) {
                $this->warn(
                    '[TÉLÉCHARGEMENT IMPOSSIBLE] '
                    . $url
                    . ' : '
                    . $e->getMessage()
                );
            }
        }

        return null;
    }

    /**
     * Vérifie qu'une photo existe et contient une image valide.
     */
    private function photoLocaleValide(?string $chemin): bool
    {
        if (!$chemin) {
            return false;
        }

        try {
            $disque = Storage::disk('public');

            if (!$disque->exists($chemin)) {
                return false;
            }

            $contenu = $disque->get($chemin);

            return @getimagesizefromstring($contenu) !== false;

        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Classe les produits dans les catégories PLANETECH.
     */
    private function classer(string $nom): array
    {
        $t = Str::lower(Str::ascii($nom));

        if (
            str_contains($t, 'lampadaire')
            || str_contains($t, 'projecteur')
        ) {
            return [
                'Énergie Solaire',
                'Lampadaires & Projecteurs Solaires',
            ];
        }

        if (
            str_contains($t, 'torche')
            || str_contains($t, 'lampe')
        ) {
            return [
                'Énergie Solaire',
                'Lampes & Torches Solaires',
            ];
        }

        if (str_contains($t, 'panneau')) {
            return [
                'Énergie Solaire',
                'Panneaux Solaires',
            ];
        }

        if (
            str_contains($t, 'batterie')
            || str_contains($t, 'onduleur')
            || str_contains($t, 'kit')
        ) {
            return [
                'Énergie Solaire',
                'Kits & Batteries Solaires',
            ];
        }

        return ['Énergie Solaire', 'Shop'];
    }

    /**
     * Nettoie la description du produit.
     */
    private function nettoyerDescription(string $texte): string
    {
        $texte = strip_tags($texte);

        $texte = preg_replace(
            '/ChatGPT a dit\s*:?\s*/iu',
            '',
            $texte
        );

        $texte = Str::squish((string) $texte);

        $phrases = preg_split(
            '/(?<=[.!?])\s+/u',
            $texte
        ) ?: [];

        $phrases = array_filter(
            $phrases,
            fn ($phrase) => !preg_match(
                '/soumari|livraison|achetez/iu',
                $phrase
            )
        );

        return Str::limit(
            trim(implode(' ', $phrases)),
            1500,
            ''
        );
    }

    /**
     * Requête HTTP vers Soumari.
     */
    private function requete(string $url)
    {
        try {
            return Http::withHeaders($this->entetes())
                ->connectTimeout(10)
                ->timeout(30)
                ->retry(2, 1000)
                ->get($url);

        } catch (Throwable $e) {
            $this->warn(
                '[CONNEXION IMPOSSIBLE] '
                . $url
                . ' : '
                . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * En-têtes HTTP.
     */
    private function entetes(): array
    {
        return [
            'User-Agent' =>
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                . 'Chrome/120.0.0.0 Safari/537.36',

            'Accept' =>
                'text/html,application/xhtml+xml,'
                . 'application/xml,image/avif,image/webp,image/*,*/*;q=0.8',

            'Accept-Language' => 'fr-FR,fr;q=0.9',

            'Referer' => 'https://www.soumari.com/',
        ];
    }
}

