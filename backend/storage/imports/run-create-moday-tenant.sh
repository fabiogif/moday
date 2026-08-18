#!/usr/bin/env bash
# Recuperacao do tenant/usuario modayrestaurante@gmail.com apos incidente de banco vazio.
# Espera que /tmp/albatec-menu.json e /tmp/import-albatec-menu.php ja existam no host
# (ja estavam la de uma importacao anterior). Roda no HOST do servidor, nao dentro do container.
set -euo pipefail

EMAIL="modayrestaurante@gmail.com"
PASSWORD="${MODAY_RECOVERY_PASSWORD:?Defina a variavel de ambiente MODAY_RECOVERY_PASSWORD antes de rodar este script}"
TENANT_NAME="Moday Restaurante"
PLAN_NAME="Plano Enterprise"

cat > /tmp/create-moday-tenant.php <<'PHPEOF'
<?php

require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantAclProvisioner;
use App\Services\TenantFinancialCategoryProvisioner;
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
PHPEOF

echo "== 1/4 arquivo /tmp/create-moday-tenant.php gravado =="

docker cp /tmp/create-moday-tenant.php moday-backend:/tmp/create-moday-tenant.php
echo "== 2/4 copiado para o container =="

docker exec moday-backend php /tmp/create-moday-tenant.php \
    --email="$EMAIL" \
    --password="$PASSWORD" \
    --name="$TENANT_NAME" \
    --plan="$PLAN_NAME"
echo "== 3/4 tenant/usuario criados =="

if [ ! -f /tmp/albatec-menu.json ] || [ ! -f /tmp/import-albatec-menu.php ]; then
    echo "ERRO: /tmp/albatec-menu.json ou /tmp/import-albatec-menu.php nao encontrados no host." >&2
    exit 1
fi

docker cp /tmp/import-albatec-menu.php moday-backend:/tmp/import-albatec-menu.php
docker cp /tmp/albatec-menu.json moday-backend:/tmp/albatec-menu.json
docker exec moday-backend php /tmp/import-albatec-menu.php /tmp/albatec-menu.json 100 --email="$EMAIL" --wipe
echo "== 4/4 cardapio importado =="
