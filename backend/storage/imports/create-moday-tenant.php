<?php

// Cria tenant + usuario dono manualmente (recuperacao de conta apos incidente).
// Espelha TenantRegistrationService::register() sem passar pelo endpoint HTTP.
//
// Uso:
//   php /tmp/create-moday-tenant.php --email=... --password=... --name=... [--plan='Plano Enterprise']
//
// Depois, importe o cardapio com o script ja existente:
//   php /tmp/import-albatec-menu.php /tmp/albatec-menu.json 100 --email=... --wipe

require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantAclProvisioner;
use App\Services\TenantFinancialCategoryProvisioner;
use App\Services\TenantOrderStatusProvisioner;
use Illuminate\Support\Facades\DB;

DB::statement('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

$email = null;
$password = null;
$name = null;
$planName = 'Plano Enterprise';

foreach ($argv as $i => $arg) {
    if ($i === 0) {
        continue;
    }
    if (str_starts_with($arg, '--email=')) {
        $email = substr($arg, strlen('--email='));
    } elseif (str_starts_with($arg, '--password=')) {
        $password = substr($arg, strlen('--password='));
    } elseif (str_starts_with($arg, '--name=')) {
        $name = substr($arg, strlen('--name='));
    } elseif (str_starts_with($arg, '--plan=')) {
        $planName = substr($arg, strlen('--plan='));
    }
}

if (!$email || !$password || !$name) {
    fwrite(STDERR, "Uso: php create-moday-tenant.php --email=... --password=... --name=... [--plan='Plano Enterprise']\n");
    exit(1);
}

if (Tenant::where('email', $email)->exists() || User::where('email', $email)->exists()) {
    fwrite(STDERR, "Ja existe tenant/usuario com o e-mail {$email}. Abortando (nada foi alterado).\n");
    exit(1);
}

$plan = Plan::where('name', $planName)->first();
if (!$plan) {
    fwrite(STDERR, "Plano nao encontrado: {$planName}. Rode o PlansTableSeeder primeiro.\n");
    exit(1);
}

DB::beginTransaction();

try {
    $tenant = Tenant::create([
        'name' => $name,
        'email' => $email,
        'plan_id' => $plan->id,
        'subscription_plan' => $plan->name,
        'is_active' => true,
        'account_status' => $plan->isFree() ? 'active' : 'trial',
    ]);

    if ($plan->isFree()) {
        $tenant->activateFreePlan($plan->name);
    } else {
        $tenant->startTrial();
    }

    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'tenant_id' => $tenant->id,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $adminProfile = app(TenantAclProvisioner::class)->provisionAndAssignOwner($tenant, $user);
    $financialCategories = app(TenantFinancialCategoryProvisioner::class)->provision($tenant);
    app(TenantOrderStatusProvisioner::class)->provision($tenant);

    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, "ERRO: {$e->getMessage()}\n{$e->getTraceAsString()}\n");
    exit(1);
}

echo "Tenant criado: id={$tenant->id} uuid={$tenant->uuid} name={$tenant->name} plan={$plan->name}\n";
echo "Usuario criado: id={$user->id} email={$user->email}\n";
echo "Perfil admin: {$adminProfile->name} ({$adminProfile->permissions->count()} permissoes)\n";
echo "Categorias financeiras criadas: {$financialCategories}\n";
echo "OK\n";
