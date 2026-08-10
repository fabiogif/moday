<?php

/**
 * Import menu JSON into a tenant by email.
 * Usage:
 *   php /tmp/import-albatec-menu.php /tmp/albatec-menu.json 100 --email=user@x.com --wipe
 */

require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

DB::statement('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

$email = 'contato@albatec.com.br';
$jsonPath = '/tmp/albatec-menu.json';
$stock = 100;
$wipe = false;

foreach ($argv as $i => $arg) {
    if ($i === 0) {
        continue;
    }
    if ($arg === '--wipe') {
        $wipe = true;
        continue;
    }
    if (str_starts_with($arg, '--email=')) {
        $email = substr($arg, strlen('--email='));
        continue;
    }
    if (is_numeric($arg)) {
        $stock = (int) $arg;
        continue;
    }
    if (is_file($arg) || str_ends_with($arg, '.json')) {
        $jsonPath = $arg;
    }
}

if (!is_file($jsonPath)) {
    fwrite(STDERR, "JSON nao encontrado: {$jsonPath}\n");
    exit(1);
}

$raw = file_get_contents($jsonPath);
if ($raw === false) {
    fwrite(STDERR, "Nao foi possivel ler: {$jsonPath}\n");
    exit(1);
}
if (!mb_check_encoding($raw, 'UTF-8')) {
    $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
}
$items = json_decode($raw, true);
if (!is_array($items) || count($items) === 0) {
    fwrite(STDERR, "JSON invalido ou vazio: " . json_last_error_msg() . "\n");
    exit(1);
}

$tenant = Tenant::where('email', $email)->first();
if (!$tenant) {
    fwrite(STDERR, "Tenant nao encontrado: {$email}\n");
    exit(1);
}

echo "Tenant: {$tenant->name} (id={$tenant->id}, email={$tenant->email})\n";
echo "Itens no JSON: " . count($items) . " | estoque={$stock}\n";

DB::beginTransaction();

try {
    if ($wipe) {
        $orderIds = collect();
        if (Schema::hasTable('orders')) {
            $orderIds = DB::table('orders')->where('tenant_id', $tenant->id)->pluck('id');
        }

        $deletedOrderItems = 0;
        $deletedOrders = 0;
        if ($orderIds->count()) {
            if (Schema::hasTable('order_product')) {
                $orderProductIds = DB::table('order_product')->whereIn('order_id', $orderIds)->pluck('id');
                if ($orderProductIds->count() && Schema::hasTable('order_item_options')) {
                    if (Schema::hasColumn('order_item_options', 'order_product_id')) {
                        $deletedOrderItems += DB::table('order_item_options')->whereIn('order_product_id', $orderProductIds)->delete();
                    } elseif (Schema::hasColumn('order_item_options', 'order_id')) {
                        $deletedOrderItems += DB::table('order_item_options')->whereIn('order_id', $orderIds)->delete();
                    }
                }
            }

            foreach (['order_evaluations', 'order_items', 'order_product', 'order_products', 'order_histories', 'order_status_histories', 'order_payments'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'order_id')) {
                    $deletedOrderItems += DB::table($table)->whereIn('order_id', $orderIds)->delete();
                }
            }
            $deletedOrders = DB::table('orders')->where('tenant_id', $tenant->id)->delete();
        }

        if (Schema::hasTable('option_group_product') && Schema::hasColumn('option_group_product', 'product_id')) {
            $productIdsForOptions = Product::withTrashed()->where('tenant_id', $tenant->id)->pluck('id');
            if ($productIdsForOptions->count()) {
                DB::table('option_group_product')->whereIn('product_id', $productIdsForOptions)->delete();
            }
        }

        $productIds = Product::withTrashed()->where('tenant_id', $tenant->id)->pluck('id');
        if ($productIds->count()) {
            DB::table('category_product')->whereIn('product_id', $productIds)->delete();
        }
        $deletedProducts = Product::withTrashed()->where('tenant_id', $tenant->id)->forceDelete();
        $deletedCategories = Category::where('tenant_id', $tenant->id)->delete();

        echo "Wipe: pedidos={$deletedOrders} itens_pedido={$deletedOrderItems} produtos={$deletedProducts} categorias={$deletedCategories}\n";
    }

    $categoryCache = [];
    $createdCats = 0;
    $createdProds = 0;
    $updatedProds = 0;
    $usedNames = [];
    $usedFlags = [];

    foreach ($items as $row) {
        $categoriaNome = trim((string) ($row['categoria'] ?? ''));
        $itemNome = trim((string) ($row['item'] ?? ''));
        $descricao = trim((string) ($row['descricao'] ?? ''));
        $preco = (float) ($row['preco'] ?? 0);
        $idProduto = (int) ($row['id_produto'] ?? 0);

        if ($categoriaNome === '' || $itemNome === '' || $idProduto <= 0) {
            throw new RuntimeException('Linha invalida: ' . json_encode($row, JSON_UNESCAPED_UNICODE));
        }

        if (!isset($categoryCache[$categoriaNome])) {
            $category = Category::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('name', $categoriaNome)
                ->first();

            if (!$category) {
                $baseUrl = Str::slug($categoriaNome);
                $url = $baseUrl !== '' ? $baseUrl : 'categoria-' . $idProduto;
                $suffix = 1;
                while (
                    Category::withoutGlobalScopes()
                        ->where('tenant_id', $tenant->id)
                        ->where('url', $url)
                        ->exists()
                ) {
                    $url = $baseUrl . '-' . $suffix;
                    $suffix++;
                }

                $category = Category::withoutEvents(function () use ($tenant, $categoriaNome, $url) {
                    return Category::withoutGlobalScopes()->create([
                        'tenant_id' => $tenant->id,
                        'name' => $categoriaNome,
                        'description' => $categoriaNome,
                        'url' => $url,
                        'status' => 'A',
                        'is_active' => true,
                        'uuid' => (string) Str::uuid(),
                    ]);
                });
                $createdCats++;
            }

            $categoryCache[$categoriaNome] = $category;
        }

        $category = $categoryCache[$categoriaNome];
        $sku = 'G-' . $idProduto;

        $productName = $descricao !== ''
            ? ($itemNome . ' - ' . $descricao)
            : $itemNome;

        if (isset($usedNames[$productName])) {
            $productName .= ' [' . $sku . ']';
        }
        $usedNames[$productName] = true;

        $description = $descricao !== '' ? $descricao : 'Sem descricao';

        $flag = Str::slug($productName);
        if ($flag === '') {
            $flag = 'produto-' . $idProduto;
        }
        $flag = $flag . '-' . $idProduto;
        if (isset($usedFlags[$flag])) {
            $flag = $flag . '-' . Str::random(4);
        }
        $usedFlags[$flag] = true;

        $product = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('sku', $sku)
            ->first();

        $payload = [
            'name' => $productName,
            'description' => $description,
            'price' => $preco,
            'qtd_stock' => $stock,
            'is_active' => true,
            'sku' => $sku,
            'brand' => isset($row['codigo']) ? (string) $row['codigo'] : null,
            'tenant_id' => $tenant->id,
            'flag' => $flag,
        ];

        if ($product) {
            Product::withoutEvents(function () use ($product, $payload) {
                $product->fill($payload);
                $product->save();
            });
            $updatedProds++;
        } else {
            $payload['uuid'] = (string) Str::uuid();
            $product = Product::withoutEvents(function () use ($payload) {
                return Product::withoutGlobalScopes()->create($payload);
            });
            $createdProds++;
        }

        $product->categories()->syncWithoutDetaching([$category->id]);
    }

    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, "ERRO: {$e->getMessage()}\n{$e->getTraceAsString()}\n");
    exit(1);
}

$total = Product::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
$cats = Category::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
$stockOk = Product::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('qtd_stock', $stock)->count();
$ordersLeft = Schema::hasTable('orders')
    ? DB::table('orders')->where('tenant_id', $tenant->id)->count()
    : 0;

echo "Categorias criadas: {$createdCats}\n";
echo "Produtos criados: {$createdProds}\n";
echo "Produtos atualizados: {$updatedProds}\n";
echo "Total produtos tenant: {$total}\n";
echo "Total categorias tenant: {$cats}\n";
echo "Produtos com estoque {$stock}: {$stockOk}\n";
echo "Pedidos restantes: {$ordersLeft}\n";
echo "OK\n";
