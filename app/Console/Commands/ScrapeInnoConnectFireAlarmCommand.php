<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ScrapeInnoConnectFireAlarmCommand extends Command
{
    protected $signature = 'scrape:innoconnect-fire-alarm
                            {--api-url= : Surcharge l\'URL Supabase si nécessaire}
                            {--api-key= : Clé API Supabase si exigée}
                            {--dry-run : Affiche les produits sans écrire en base}';

    protected $description = 'Scrape les produits Fire Alarm d\'InnoConnect dans Sécurité Bâtiment > Détecteurs de Fumée & Incendie';

    private const DEFAULT_API_URL = 'https://aoolwybpbuyokrjufrzi.supabase.co/rest/v1/products?select=id%2Cslug%2Cname%2Csku%2Cprice%2Ccurrency%2Cimages%2Cstock_status%2Cis_featured%2Cbrand%3Abrands%28name%29&category_id=eq.79a2541a-66da-4a57-9e67-de7780c3b6bb&is_active=eq.true&order=is_featured.desc%2Csort_order.asc&limit=60';

    public function handle(): int
    {
        $apiUrl = trim((string) ($this->option('api-url') ?: self::DEFAULT_API_URL));
        $apiKey = trim((string) ($this->option('api-key') ?: env('INNOCONNECT_SUPABASE_KEY', env('INNOCONNECT_API_KEY', ''))));
        $dryRun = (bool) $this->option('dry-run');

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept' => 'application/json',
            'Referer' => 'https://www.innoconnect.ma/categories/fire-alarm',
        ];

        if ($apiKey !== '') {
            $headers['apikey'] = $apiKey;
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        $this->info("Récupération via API : {$apiUrl}");

        try {
            $response = Http::timeout(25)
                ->withHeaders($headers)
                ->get($apiUrl);
        } catch (\Throwable $e) {
            $this->error('Erreur réseau : ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error("Échec HTTP ({$response->status()}) : " . Str::limit($response->body(), 400));
            return self::FAILURE;
        }

        $rows = $this->extractRows($response->json());

        if (empty($rows)) {
            $this->warn('Aucun produit trouvé dans la réponse API.');
            return self::SUCCESS;
        }

        $products = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $nom = trim((string) ($row['name'] ?? ''));
            if ($nom === '') {
                continue;
            }

            $rawPrice = $row['price'] ?? null;
            $parsedPrice = is_numeric($rawPrice) ? (float) $rawPrice : null;

            // Produit considéré "Sur devis" si le prix est absent, null ou <= 0
            $surDevis = $parsedPrice === null || $parsedPrice <= 0;
            $prixNum = $surDevis ? 0.0 : $parsedPrice;

            $brandName = is_array($row['brand'] ?? null) ? ($row['brand']['name'] ?? null) : null;
            $image = $this->extractFirstImage($row['images'] ?? null);
            $slug = trim((string) ($row['slug'] ?? '')) ?: Str::slug($nom);

            $products[] = [
                'nom' => $nom,
                'slug' => $slug,
                'sku' => $row['sku'] ?? null,
                'marque' => $brandName,
                'devise' => $row['currency'] ?? null,
                'stock_status' => $row['stock_status'] ?? null,
                'sur_devis' => $surDevis,
                'prix_num' => $prixNum,
                'prix_label' => $surDevis ? 'Sur devis' : number_format($prixNum, 2, '.', ''),
                'image' => $image ? $this->absolutizeUrl($image) : null,
                'source_url' => 'https://www.innoconnect.ma/products/' . ltrim($slug, '/'),
                'description' => trim($nom . ($brandName ? " - Marque : {$brandName}" : '')),
            ];
        }

        $this->info(count($products) . ' produit(s) extrait(s).');

        if ($dryRun) {
            $this->table(
                ['Nom', 'Marque', 'SKU', 'Tarif', 'Stock'],
                array_map(fn ($p) => [
                    Str::limit($p['nom'], 38),
                    $p['marque'] ?? '-',
                    $p['sku'] ?? '-',
                    $p['prix_label'],
                    $p['stock_status'] ?? '-',
                ], $products)
            );
            return self::SUCCESS;
        }

        [$categorie, $sousCategorie] = $this->resolveCategories();

        $saved = 0;
        foreach ($products as $item) {
            $this->persistProduct($item, $categorie, $sousCategorie);
            $saved++;
        }

        $this->info("{$saved} produit(s) synchronisé(s) dans [Sécurité Bâtiment > Détecteurs de Fumée & Incendie].");

        return self::SUCCESS;
    }

    protected function extractFirstImage(mixed $images): ?string
    {
        if (is_string($images) && $images !== '') {
            $decoded = json_decode($images, true);
            if (is_array($decoded) && ! empty($decoded)) {
                $first = $decoded[0];
                return is_array($first) ? ($first['url'] ?? null) : (is_string($first) ? $first : null);
            }
            return $images;
        }

        if (is_array($images) && ! empty($images)) {
            $first = $images[0];
            if (is_array($first)) {
                return $first['url'] ?? null;
            }
            return is_string($first) ? $first : null;
        }

        return null;
    }

    protected function extractRows(mixed $json): array
    {
        if (! is_array($json)) {
            return [];
        }

        if (array_is_list($json)) {
            return $json;
        }

        foreach (['data', 'products', 'items', 'results'] as $key) {
            if (isset($json[$key]) && is_array($json[$key])) {
                return $json[$key];
            }
        }

        return [];
    }

    protected function resolveCategories(): array
    {
        $categoryModel = class_exists(\App\Models\Category::class)
            ? \App\Models\Category::class
            : \App\Models\Categorie::class;

        $categorie = $categoryModel::firstOrCreate([
            'nom' => 'Sécurité Bâtiment',
        ]);

        $subCategoryModel = class_exists(\App\Models\SousCategorie::class)
            ? \App\Models\SousCategorie::class
            : \App\Models\SubCategory::class;

        $subTable = (new $subCategoryModel())->getTable();
        $parentColumn = Schema::hasColumn($subTable, 'category_id')
            ? 'category_id'
            : 'categorie_id';

        $sousCategorie = $subCategoryModel::firstOrCreate([
            $parentColumn => $categorie->id,
            'nom' => 'Détecteurs de Fumée & Incendie',
        ]);

        return [$categorie, $sousCategorie];
    }

    protected function persistProduct(array $item, $categorie, $sousCategorie): void
    {
        $productModel = class_exists(\App\Models\Produit::class)
            ? \App\Models\Produit::class
            : \App\Models\Product::class;

        $table = (new $productModel())->getTable();
        $lookup = Schema::hasColumn($table, 'slug')
            ? ['slug' => $item['slug']]
            : ['nom' => $item['nom']];

        $payload = [];

        if (Schema::hasColumn($table, 'nom')) {
            $payload['nom'] = $item['nom'];
        }
        if (Schema::hasColumn($table, 'slug')) {
            $payload['slug'] = $item['slug'];
        }
        if (Schema::hasColumn($table, 'sku') && $item['sku']) {
            $payload['sku'] = $item['sku'];
        }
        if (Schema::hasColumn($table, 'marque') && $item['marque']) {
            $payload['marque'] = $item['marque'];
        }
        if (Schema::hasColumn($table, 'category_id')) {
            $payload['category_id'] = $categorie->id;
        } elseif (Schema::hasColumn($table, 'categorie_id')) {
            $payload['categorie_id'] = $categorie->id;
        }
        if (Schema::hasColumn($table, 'sous_categorie_id')) {
            $payload['sous_categorie_id'] = $sousCategorie->id;
        } elseif (Schema::hasColumn($table, 'sub_category_id')) {
            $payload['sub_category_id'] = $sousCategorie->id;
        }
        if (Schema::hasColumn($table, 'prix')) {
            $payload['prix'] = $item['prix_num'];
        }
        if (Schema::hasColumn($table, 'sur_devis')) {
            $payload['sur_devis'] = $item['sur_devis'];
        }
        if (Schema::hasColumn($table, 'image')) {
            $payload['image'] = $item['image'];
        }
        if (Schema::hasColumn($table, 'source_url')) {
            $payload['source_url'] = $item['source_url'];
        }
        if (Schema::hasColumn($table, 'description')) {
            $payload['description'] = $item['description'];
        }

        $productModel::updateOrCreate($lookup, $payload);
    }

    protected function absolutizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }
        return 'https://www.innoconnect.ma' . (Str::startsWith($url, '/') ? '' : '/') . $url;
    }
}
