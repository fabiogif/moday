<?php

/**
 * Script para adicionar Payment Methods a um tenant
 * 
 * Uso: php scripts/add-payment-methods-to-tenant.php [slug-do-tenant]
 * Exemplo: php scripts/add-payment-methods-to-tenant.php empresa-dev
 */

require __dir__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\PaymentMethod;
use Illuminate\Support\Str;

// Pegar slug do tenant via argumento ou usar padrão
$slug = $argv[1] ?? 'empresa-dev';

echo "\n🔍 Procurando tenant: '{$slug}'...\n";

// Buscar tenant
$tenant = Tenant::where('slug', $slug)->first();

if (!$tenant) {
    echo "❌ ERRO: Tenant '{$slug}' não encontrado!\n";
    echo "\n💡 Tenants disponíveis:\n";
    
    $tenants = Tenant::all();
    foreach ($tenants as $t) {
        echo "   - {$t->slug} ({$t->name})\n";
    }
    
    exit(1);
}

echo "✅ Tenant encontrado: {$tenant->name} (ID: {$tenant->id})\n\n";

// Verificar payment methods existentes
$existing = PaymentMethod::where('tenant_id', $tenant->id)->get();

if ($existing->count() > 0) {
    echo "⚠️  Este tenant já possui {$existing->count()} forma(s) de pagamento:\n";
    foreach ($existing as $method) {
        $status = $method->is_active ? '✓' : '✗';
        echo "   {$status} {$method->name} ({$method->type})\n";
    }
    
    echo "\n❓ Deseja adicionar mais formas de pagamento? (s/N): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 's') {
        echo "\n👋 Operação cancelada.\n\n";
        exit(0);
    }
}

// Formas de pagamento padrão
$paymentMethods = [
    [
        'name' => 'PIX',
        'description' => 'Pagamento instantâneo via PIX',
        'type' => 'pix'
    ],
    [
        'name' => 'Dinheiro',
        'description' => 'Pagamento em dinheiro',
        'type' => 'cash'
    ],
    [
        'name' => 'Cartão de Crédito',
        'description' => 'Visa, Mastercard, Elo',
        'type' => 'credit_card'
    ],
    [
        'name' => 'Cartão de Débito',
        'description' => 'Débito em conta corrente',
        'type' => 'debit_card'
    ],
    [
        'name' => 'Ticket Refeição',
        'description' => 'Vale refeição/alimentação',
        'type' => 'ticket'
    ],
    [
        'name' => 'Voucher',
        'description' => 'Voucher/cupom',
        'type' => 'voucher'
    ],
];

echo "\n📝 Criando formas de pagamento...\n\n";

$created = 0;
$skipped = 0;

foreach ($paymentMethods as $method) {
    $exists = PaymentMethod::where('tenant_id', $tenant->id)
        ->where('name', $method['name'])
        ->first();
    
    if ($exists) {
        echo "⏭️  '{$method['name']}' já existe (pulando)\n";
        $skipped++;
        continue;
    }
    
    try {
        PaymentMethod::create([
            'uuid' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => $method['name'],
            'description' => $method['description'],
            'type' => $method['type'],
            'is_active' => true,
        ]);
        
        echo "✅ Criado: {$method['name']}\n";
        $created++;
    } catch (\Exception $e) {
        echo "❌ Erro ao criar '{$method['name']}': {$e->getMessage()}\n";
    }
}

echo "\n" . str_repeat('─', 50) . "\n";
echo "🎉 Concluído!\n";
echo "   ✅ Criados: {$created}\n";
echo "   ⏭️  Pulados: {$skipped}\n";

// Listar todas as formas de pagamento do tenant
$all = PaymentMethod::where('tenant_id', $tenant->id)
    ->orderBy('name')
    ->get();

echo "\n📋 Formas de pagamento ativas para '{$tenant->name}':\n";
foreach ($all as $method) {
    $status = $method->is_active ? '✓ Ativo' : '✗ Inativo';
    echo "   • {$method->name} ({$method->type}) - {$status}\n";
}

echo "\n✅ Tudo pronto! Você pode testar em:\n";
echo "   http://localhost/api/store/{$slug}/payment-methods\n\n";

exit(0);

