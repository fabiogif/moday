<?php

namespace Tests\Feature;

use App\Models\OrderStatus;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantOrderStatusProvisioner;
use Database\Seeders\DefaultOrderStatusesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantOrderStatusProvisionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registro_self_service_cria_status_padrao_do_pedido(): void
    {
        Plan::factory()->create(['is_active' => true, 'price' => 0]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Usuário Status',
            'email' => 'status.auth@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'status.auth@example.com')->firstOrFail();
        $this->assertCanonicalStatusesForTenant((int) $user->tenant_id);
    }

    #[Test]
    public function registro_de_empresa_cria_status_padrao_do_pedido(): void
    {
        $plan = Plan::factory()->create(['is_active' => true, 'price' => 0]);

        $response = $this->postJson('/api/register', [
            'company_name' => 'Empresa Status',
            'company_email' => 'empresa-status@test.com',
            'company_cnpj' => '11222333000181',
            'name' => 'Admin Status',
            'email' => 'admin-status@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(201);

        $tenant = Tenant::where('email', 'empresa-status@test.com')->firstOrFail();
        $this->assertCanonicalStatusesForTenant((int) $tenant->id);
    }

    #[Test]
    public function provisioner_e_idempotente_por_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $provisioner = app(TenantOrderStatusProvisioner::class);

        $provisioner->provision($tenant);
        $provisioner->provision($tenant);

        $this->assertCanonicalStatusesForTenant((int) $tenant->id);
    }

    private function assertCanonicalStatusesForTenant(int $tenantId): void
    {
        $names = OrderStatus::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('order_position')
            ->pluck('name')
            ->all();

        $this->assertSame(
            ['Pendente', 'Aceito', 'Preparo', 'Entrega', 'Concluído', 'Cancelado'],
            $names
        );
        $this->assertCount(count(DefaultOrderStatusesSeeder::definitions()), $names);
    }
}
