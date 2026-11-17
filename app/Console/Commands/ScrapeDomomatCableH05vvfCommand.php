<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\SousCategorie;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeDomomatCableH05vvfCommand extends Command
{
    protected $signature = 'scrape:domomat-h05vvf
                            {url=https://etechnologies.shop/product-category/cablage/ : URL de catégorie à scraper}
                            {--fallback=https://etechnologies.shop/product-category/onduleurs/ : URL de secours si 404/403}
                            {--force : Mettre à jour même si la photo existe déjà}';

    protected $description = 'Scrape les câbles et produits électriques/réseaux (sur devis, sans prix) avec classement automatique';

    public function handle(): int
    {
        $rawUrl = trim((string) $this->argument('url'));
        $primaryUrl = rtrim(preg_replace('#^http://#i', 'https://', $rawUrl), '/');
        $fallbackUrl = rtrim(trim((string) $this->option('fallback')), '/');
        $force = (bool) $this->option('force');

        $this->info("Début du scraping sur {$primaryUrl}/ ...");

        // 1. Catégories principales
        $electricite = Category::firstOrCreate(
            ['nom' => 'Électricité Générale'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $reseaux = Category::firstOrCreate(
            ['nom' => 'Réseaux & Télécoms'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // 2. Gestion sécurisée des Sous-catégories associées
        $electriciteSubsList = [
            'Matériel HT (Haute Tension) / MT (Moyenne Tension)',
            'Matériel BT (Basse Tension)',
            'Câbles et Conducteurs',
            'Transformateurs',
            'Appareillage et Accessoires Domestiques / Tertiaires',
            'Accessoires de Pose, Fixation et Raccordement',
        ];

        $reseauxSubsList = [
            'Câbles Réseau et Télécoms',
            'Fibre Optique',
            'Connectique, Prises et Brassage',
            'Équipements Actifs (Switchs, Routeurs, Wi-Fi)',
            'Baies et Coffrets de Brassage',
            'Téléphonie et Communication',
            'Antennes, TV et Satellite',
            'Vidéosurveillance IP',
            'Outillage et Mesure Réseau',
        ];

        $subMap = [];

        foreach ($electriciteSubsList as $nomSub) {
            $subMap['Électricité Générale|' . $nomSub] = SousCategorie::firstOrCreate(
                [
                    'category_id' => $electricite->id,
                    'nom' => $nomSub,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        foreach ($reseauxSubsList as $nomSub) {
            $subMap['Réseaux & Télécoms|' . $nomSub] = SousCategorie::firstOrCreate(
                [
                    'category_id' => $reseaux->id,
                    'nom' => $nomSub,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $activeBaseUrl = $primaryUrl;
        $totalInserted = 0;
        $totalUpdated = 0;

        for ($page = 1; $page <= 20; $page++) {
            $url = $page === 1 ? $activeBaseUrl . '/' : $activeBaseUrl . '/page/' . $page . '/';
            $this->info("\n--- Page {$page} : {$url} ---");

            $fetch = $this->fetchHtml($url);

            if ($page === 1 && ($fetch['status'] === 404 || $fetch['status'] === 403 || $fetch['blocked']) && $fallbackUrl !== '') {
                $this->warn("Statut {$fetch['status']} sur {$primaryUrl}/ -> bascule sur {$fallbackUrl}/");
                $activeBaseUrl = $fallbackUrl;
                $url = $activeBaseUrl . '/';
                $fetch = $this->fetchHtml($url);
            }

            if ($fetch['status'] === 404) {
                $this->line('Fin de pagination (404).');
                break;
            }

            if ($fetch['blocked'] || $fetch['status'] === 403) {
                $this->warn("Blocage anti-bot (HTTP {$fetch['status']}) sur {$url}.");
                break;
            }

            if ($fetch['failed'] || $fetch['body'] === '') {
                $this->warn("Erreur HTTP {$fetch['status']} sur {$url}");
                break;
            }

            $crawler = new Crawler($fetch['body']);
            $nodes = $this->extractProductNodes($crawler);
            $this->info('Produits détectés : ' . $nodes->count() . ' (HTTP ' . $fetch['status'] . ')');

            if ($nodes->count() === 0) {
                break;
            }

            foreach ($nodes as $domElement) {
                try {
                    if (!$domElement instanceof \DOMElement) {
                        continue;
                    }

                    $node = new Crawler($domElement);
                    $name = $this->extractProductName($node);

                    if ($this->isJunkName($name)) {
                        continue;
                    }

                    $targetSubCat = $this->resolveTargetSousCategorie($name, $subMap);
                    $productUrl = $this->extractProductUrl($node, $activeBaseUrl);

                    $product = Product::where('sous_categorie_id', $targetSubCat->id)
                        ->where('name', $name)
                        ->first();

                    if ($product && !empty($product->photo) && !$force) {
                        continue;
                    }

                    $imageUrl = $this->extractImageUrl($node, $productUrl, $activeBaseUrl . '/');
                    $localImage = $this->downloadImage($imageUrl, $name, $activeBaseUrl . '/');

                    $desc = $targetSubCat->category_id === $reseaux->id
                        ? 'Produit Réseaux & Télécoms — ' . $targetSubCat->nom . ' (Sur devis).'
                        : 'Produit Électricité Générale — ' . $targetSubCat->nom . ' (Sur devis).';

                    if ($product) {
                        $product->update([
                            'price' => null,
                            'photo' => $localImage ?? $product->photo,
                            'description' => $desc,
                        ]);
                        $totalUpdated++;
                        $this->line('  [🔄] Mis à jour (Sur devis) : ' . Str::limit($name, 45) . ' -> [' . $targetSubCat->nom . ']');
                    } else {
                        Product::create([
                            'sous_categorie_id' => $targetSubCat->id,
                            'name' => $name,
                            'price' => null,
                            'photo' => $localImage,
                            'description' => $desc,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $totalInserted++;
                        $this->line('  [✔] Inséré (Sur devis) : ' . Str::limit($name, 45) . ' -> [' . $targetSubCat->nom . ']');
                    }
                } catch (\Throwable $e) {
                    $this->error('  [X] Erreur produit : ' . $e->getMessage());
                }
            }

            $nextPage = $crawler->filter('a.next.page-numbers, .woocommerce-pagination a.next, [rel="next"]');
            if ($nextPage->count() === 0) {
                break;
            }
        }

        $this->info("\nTerminé ! Insérés : {$totalInserted} | Mis à jour : {$totalUpdated}");
        return self::SUCCESS;
    }

    /**
     * Classe produit par produit vers Réseaux & Télécoms ou Électricité Générale.
     *
     * @param array<string, SousCategorie> $subMap
     */
    private function resolveTargetSousCategorie(string $name, array $subMap): SousCategorie
    {
        $lower = mb_strtolower($name);

        // 1. Priorité Réseaux & Télécoms
        if (preg_match('/\b(fibre|optique|ftth|sfp|jarretière|jarretiere)\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Fibre Optique'];
        }

        if (preg_match('/\b(baie|coffret\s+de\s+brassage|rack|19\s*pouces|19")\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Baies et Coffrets de Brassage'];
        }

        if (preg_match('/\b(switch|routeur|router|wi-?fi|access\s*point|point\s+d[\'’]?acc[eè]s|répéteur|repeteur|cpe|modem)\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Équipements Actifs (Switchs, Routeurs, Wi-Fi)'];
        }

        if (preg_match('/\b(téléphon|telephon|interphon|visiophon|pbx|ipbx|sip|dekt|dect)\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Téléphonie et Communication'];
        }

        if (preg_match('/\b(antenne|tnt|satellite|lnb|tv|coaxial|coax)\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Antennes, TV et Satellite'];
        }

        if (preg_match('/\b(caméra\s*ip|camera\s*ip|vidéosurveillance|videosurveillance|nvr|dvr)\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Vidéosurveillance IP'];
        }

        if (preg_match('/\b(testeur|sertisseuse|pince\s+à\s+sertir|reflectom|réflectom|mesure\s+réseau|mesure\s+reseau|fluke)\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Outillage et Mesure Réseau'];
        }

        if (preg_match('/\b(rj\s*45|keystone|patch\s*panel|panneau\s+de\s+brassage|cordon\s+de\s+brassage|prise\s+rj|connecteur\s+rj)\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Connectique, Prises et Brassage'];
        }

        if (preg_match('/\b(cat\s*[5678]e?|utp|ftp|sftp|fftp|ethernet|câble\s+réseau|cable\s+reseau)\b/i', $lower)) {
            return $subMap['Réseaux & Télécoms|Câbles Réseau et Télécoms'];
        }

        // 2. Sinon Électricité Générale
        if (preg_match('/\b(transformateur|transfo)\b/i', $lower)) {
            return $subMap['Électricité Générale|Transformateurs'];
        }

        if (preg_match('/\b(hta|mtb|haute\s+tension|moyenne\s+tension|cellule\s+hta)\b/i', $lower)) {
            return $subMap['Électricité Générale|Matériel HT (Haute Tension) / MT (Moyenne Tension)'];
        }

        if (preg_match('/\b(disjoncteur|différentiel|differentiel|contacteur|relais|coffret\s+électrique|coffret\s+electrique|tableau\s+électrique|tableau\s+electrique|parafoudre)\b/i', $lower)) {
            return $subMap['Électricité Générale|Matériel BT (Basse Tension)'];
        }

        if (preg_match('/\b(interrupteur|prise\s+de\s+courant|va-et-vient|poussoir|appareillage|luminaire|hublot|réglette|reglette)\b/i', $lower)) {
            return $subMap['Électricité Générale|Appareillage et Accessoires Domestiques / Tertiaires'];
        }

        if (preg_match('/\b(goulotte|gaine\s+ict|collier|presse-étoupe|presse-etoupe|cosse|borne\s+de\s+connexion|domino|embout|fixation|raccordement)\b/i', $lower)) {
            return $subMap['Électricité Générale|Accessoires de Pose, Fixation et Raccordement'];
        }

        return $subMap['Électricité Générale|Câbles et Conducteurs'];
    }

    private function fetchHtml(string $url): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
            ])
                ->withOptions(['verify' => false])
                ->connectTimeout(25)
                ->timeout(60)
                ->retry(2, 2500)
                ->get($url);
        } catch (ConnectionException $e) {
            $this->warn('Erreur réseau : ' . $e->getMessage());
            return ['status' => 0, 'body' => '', 'blocked' => false, 'failed' => true];
        }

        $status = $response->status();
        $body = $response->body();
        $blocked = $status === 403 || str_contains($body, 'Just a moment...');

        return [
            'status' => $status,
            'body' => $body,
            'blocked' => $blocked,
            'failed' => $response->failed(),
        ];
    }

    private function extractProductNodes(Crawler $crawler): Crawler
    {
        $selectors = [
            'ul.products li.product',
            '.products .product',
            'li.type-product',
            'div.product.type-product',
            'article.product',
            '[data-product-id]',
        ];

        foreach ($selectors as $selector) {
            $found = $crawler->filter($selector);
            if ($found->count() > 0) {
                return $found;
            }
        }

        return $crawler->filter('a[href*="/product/"]');
    }

    private function extractProductName(Crawler $node): string
    {
        foreach (['.woocommerce-loop-product__title', 'h2.woocommerce-loop-product__title', 'h2', 'h3'] as $selector) {
            $titleNode = $node->filter($selector);
            if ($titleNode->count() > 0) {
                $candidate = trim($titleNode->first()->text());
                if (!$this->isJunkName($candidate)) {
                    return $candidate;
                }
            }
        }

        foreach ($node->filter('a') as $el) {
            if ($el instanceof \DOMElement) {
                $parentClass = $el->parentNode instanceof \DOMElement ? (string) $el->parentNode->getAttribute('class') : '';
                $elClass = (string) $el->getAttribute('class');
                if (str_contains($elClass, 'compare') || str_contains($parentClass, 'compare')) {
                    continue;
                }

                $txt = trim((string) $el->textContent);
                if (!$this->isJunkName($txt)) {
                    return $txt;
                }
            }
        }

        return '';
    }

    private function isJunkName(string $name): bool
    {
        $clean = trim($name);
        if ($clean === '' || mb_strlen($clean) <= 5) {
            return true;
        }

        $lower = mb_strtolower($clean);
        $forbidden = [
            'comparer',
            'ajouter',
            'lire la suite',
            'voir le produit',
            'sélectionner des options',
            'apercu rapide',
            'aperçu rapide',
        ];

        foreach ($forbidden as $word) {
            if ($lower === $word || str_contains($lower, $word)) {
                return true;
            }
        }

        return false;
    }

    private function extractProductUrl(Crawler $node, string $baseUrl): string
    {
        $linkNode = $node->filter('a.woocommerce-LoopProduct-link, h2.woocommerce-loop-product__title a, .woocommerce-loop-product__title a, h2 a, h3 a, a[href*="/product/"]');
        if ($linkNode->count() > 0) {
            $href = trim((string) $linkNode->first()->attr('href'));
            if ($href !== '' && !Str::startsWith($href, ['http://', 'https://'])) {
                return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
            }
            return $href;
        }

        return '';
    }

    private function extractImageUrl(Crawler $node, ?string $productUrl, string $referer): ?string
    {
        $imgNode = $node->filter('img.attachment-woocommerce_thumbnail, img.wp-post-image, img');
        if ($imgNode->count() > 0) {
            $src = trim((string) ($imgNode->first()->attr('data-src') ?: $imgNode->first()->attr('src')));
            if ($src !== '' && !Str::contains($src, ['data:image', 'placeholder'])) {
                return str_starts_with($src, '//') ? 'https:' . $src : $src;
            }
        }

        if (empty($productUrl)) {
            return null;
        }

        $fetch = $this->fetchHtml($productUrl);
        if (!$fetch['failed'] && $fetch['body'] !== '') {
            $crawler = new Crawler($fetch['body']);
            $ogImage = $crawler->filter('meta[property="og:image"]');
            if ($ogImage->count() > 0) {
                $url = trim((string) $ogImage->first()->attr('content'));
                return str_starts_with($url, '//') ? 'https:' . $url : $url;
            }
        }

        return null;
    }

    private function downloadImage(?string $imageUrl, string $name, string $referer): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Referer' => $referer,
            ])
                ->withOptions(['verify' => false])
                ->connectTimeout(15)
                ->timeout(30)
                ->get($imageUrl);

            if ($response->successful() && !empty($response->body())) {
                $imageName = Str::slug(Str::limit($name, 40, '')) . '-' . time() . '-' . random_int(100, 999) . '.jpg';
                Storage::disk('public')->put('products/' . $imageName, $response->body());
                return 'storage/products/' . $imageName;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }
}
