<?php

require_once 'vendor/autoload.php';

use App\Services\ClientService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testando Cache do Redis para Clientes\n";
echo "=====================================\n\n";

try {
    // Criar um tenant e usuário para teste
    $tenant = Tenant::first();
    if (!$tenant) {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'uuid' => 'test-tenant']);
        echo "✅ Tenant criado: {$tenant->name}\n";
    } else {
        echo "✅ Tenant encontrado: {$tenant->name}\n";
    }

    $user = User::first();
    if (!$user) {
        $user = User::create([
            'name' => 'Test User', 
            'email' => 'test@example.com', 
            'password' => bcrypt('password'), 
            'tenant_id' => $tenant->id
        ]);
        echo "✅ Usuário criado: {$user->name}\n";
    } else {
        echo "✅ Usuário encontrado: {$user->name}\n";
    }

    // Testar cache
    $clientService = app(ClientService::class);
    echo "\n🔄 Testando cache de clientes...\n";

    // Primeira chamada (deve ir ao banco)
    $start = microtime(true);
    $stats1 = $clientService->getClientStats($tenant->id);
    $time1 = microtime(true) - $start;
    echo "⏱️  Primeira chamada: " . round($time1 * 1000, 2) . "ms\n";

    // Segunda chamada (deve usar cache)
    $start = microtime(true);
    $stats2 = $clientService->getClientStats($tenant->id);
    $time2 = microtime(true) - $start;
    echo "⏱️  Segunda chamada: " . round($time2 * 1000, 2) . "ms\n";

    // Verificar se cache está funcionando
    $cacheWorking = $time2 < $time1;
    $dataEqual = $stats1 === $stats2;
    
    echo "\n📊 Resultados:\n";
    echo "Cache funcionando: " . ($cacheWorking ? "✅ SIM" : "❌ NÃO") . "\n";
    echo "Dados iguais: " . ($dataEqual ? "✅ SIM" : "❌ NÃO") . "\n";
    
    // Verificar se chave de cache existe
    $cacheKey = "client_stats_{$tenant->id}";
    $cacheExists = Cache::has($cacheKey);
    echo "Chave de cache existe: " . ($cacheExists ? "✅ SIM" : "❌ NÃO") . "\n";
    echo "Chave: {$cacheKey}\n";
    
    // Mostrar estatísticas
    echo "\n📈 Estatísticas retornadas:\n";
    echo json_encode($stats1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    echo "\n\n🎯 Teste concluído!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
