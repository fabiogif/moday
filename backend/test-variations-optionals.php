<?php

/**
 * Script de teste para verificar se variations e optionals
 * estão sendo salvos e recuperados corretamente
 * 
 * Execute: php artisan tinker
 * Depois copie e cole este código
 */

// Buscar o primeiro produto que tem variations ou optionals
$product = \App\Models\Product::whereNotNull('variations')
    ->orWhereNotNull('optionals')
    ->first();

if (!$product) {
    echo "❌ Nenhum produto com variations/optionals encontrado\n";
    echo "Execute: php artisan db:seed --class=ProductSeeder\n";
    exit;
}

echo "=== TESTE DE VARIATIONS E OPTIONALS ===\n\n";

echo "📦 Produto: {$product->name}\n";
echo "🆔 ID: {$product->id}\n";
echo "🔑 UUID: {$product->uuid}\n\n";

// Testar variations
echo "🔍 VARIATIONS:\n";
echo "- Tipo: " . gettype($product->variations) . "\n";
echo "- É Array? " . (is_array($product->variations) ? 'SIM ✅' : 'NÃO ❌') . "\n";
echo "- Quantidade: " . (is_array($product->variations) ? count($product->variations) : 0) . "\n";

if (is_array($product->variations) && count($product->variations) > 0) {
    echo "- Conteúdo:\n";
    foreach ($product->variations as $variation) {
        echo "  • {$variation['name']}: R$ {$variation['price']}\n";
    }
} else {
    echo "- Conteúdo: Vazio ou inválido\n";
}

echo "\n";

// Testar optionals
echo "🔍 OPTIONALS:\n";
echo "- Tipo: " . gettype($product->optionals) . "\n";
echo "- É Array? " . (is_array($product->optionals) ? 'SIM ✅' : 'NÃO ❌') . "\n";
echo "- Quantidade: " . (is_array($product->optionals) ? count($product->optionals) : 0) . "\n";

if (is_array($product->optionals) && count($product->optionals) > 0) {
    echo "- Conteúdo:\n";
    foreach ($product->optionals as $optional) {
        echo "  • {$optional['name']}: R$ {$optional['price']}\n";
    }
} else {
    echo "- Conteúdo: Vazio ou inválido\n";
}

echo "\n";

// Testar API Resource
echo "🔍 API RESOURCE:\n";
$resource = new \App\Http\Resources\ProductResource($product);
$arrayData = $resource->toArray(request());

echo "- Variations no Resource:\n";
if (isset($arrayData['variations']) && is_array($arrayData['variations'])) {
    echo "  ✅ Existe e é array (" . count($arrayData['variations']) . " itens)\n";
    foreach ($arrayData['variations'] as $variation) {
        echo "    • {$variation['name']}: R$ {$variation['price']}\n";
    }
} else {
    echo "  ❌ Não existe ou não é array\n";
}

echo "- Optionals no Resource:\n";
if (isset($arrayData['optionals']) && is_array($arrayData['optionals'])) {
    echo "  ✅ Existe e é array (" . count($arrayData['optionals']) . " itens)\n";
    foreach ($arrayData['optionals'] as $optional) {
        echo "    • {$optional['name']}: R$ {$optional['price']}\n";
    }
} else {
    echo "  ❌ Não existe ou não é array\n";
}

echo "\n";

// Verificar banco de dados diretamente
echo "🔍 BANCO DE DADOS (RAW):\n";
$rawData = \DB::table('products')->where('id', $product->id)->first();
echo "- variations (raw): " . substr($rawData->variations ?? 'NULL', 0, 100) . "...\n";
echo "- optionals (raw): " . substr($rawData->optionals ?? 'NULL', 0, 100) . "...\n";

echo "\n=== FIM DO TESTE ===\n";

