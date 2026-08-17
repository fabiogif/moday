<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\PlanMigration;
use App\Services\PlanMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanMigrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PlanMigrationService $service;
    protected Tenant $tenant;
    protected Plan $planGratis;
    protected Plan $planBasico;
    protected Plan $planPremium;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlanMigrationService::class);

        // Criar planos
        $this->planGratis = Plan::create([
            'name' => 'Grátis',
            'url' => 'gratis',
            'price' => 0.00,
            'description' => 'Plano gratuito',
            'is_active' => true,
            'max_users' => 1,
            'max_products' => 50,
            'max_orders_per_month' => 30,
            'has_marketing' => false,
            'has_reports' => false,
        ]);

        $this->planBasico = Plan::create([
            'name' => 'Básico',
            'url' => 'basico',
            'price' => 49.90,
            'description' => 'Plano básico',
            'is_active' => true,
            'max_users' => 5,
            'max_products' => 100,
            'max_orders_per_month' => 100,
            'has_marketing' => true,
            'has_reports' => true,
        ]);

        $this->planPremium = Plan::create([
            'name' => 'Premium',
            'url' => 'premium',
            'price' => 99.90,
            'description' => 'Plano premium',
            'is_active' => true,
            'max_users' => 999999,
            'max_products' => 999999,
            'max_orders_per_month' => null,
            'has_marketing' => true,
            'has_reports' => true,
        ]);

        // Criar tenant com plano Grátis
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $this->planGratis->id,
        ]);
    }

    public function test_migrate_plan_successfully(): void
    {
        $result = $this->service->migratePlan(
            $this->tenant->id,
            $this->planBasico->id,
            'Migração de teste'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Plano migrado com sucesso.', $result['message']);
        
        // Verificar se tenant foi atualizado
        $this->tenant->refresh();
        $this->assertEquals($this->planBasico->id, $this->tenant->plan_id);
        $this->assertEquals('Básico', $this->tenant->subscription_plan);

        // Verificar se migração foi registrada
        $migration = PlanMigration::where('tenant_id', $this->tenant->id)
            ->where('to_plan_id', $this->planBasico->id)
            ->first();

        $this->assertNotNull($migration);
        $this->assertEquals($this->planGratis->id, $migration->from_plan_id);
        $this->assertEquals('completed', $migration->status);
        $this->assertEquals('Migração de teste', $migration->notes);
    }

    public function test_migrate_plan_updates_mrr(): void
    {
        $result = $this->service->migratePlan(
            $this->tenant->id,
            $this->planBasico->id
        );

        $this->tenant->refresh();
        $this->assertEquals(49.90, $this->tenant->mrr);
    }

    public function test_migrate_plan_throws_exception_if_plan_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plano não encontrado.');

        $this->service->migratePlan($this->tenant->id, 99999);
    }

    public function test_migrate_plan_throws_exception_if_plan_inactive(): void
    {
        // Desativar plano
        $this->planBasico->update(['is_active' => false]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('O plano selecionado não está ativo.');

        $this->service->migratePlan($this->tenant->id, $this->planBasico->id);
    }

    public function test_migrate_plan_throws_exception_if_same_plan(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('O tenant já está neste plano.');

        $this->service->migratePlan($this->tenant->id, $this->planGratis->id);
    }

    public function test_migrate_plan_handles_tenant_without_plan(): void
    {
        // Remover plano do tenant
        $this->tenant->update(['plan_id' => null]);

        $result = $this->service->migratePlan(
            $this->tenant->id,
            $this->planBasico->id
        );

        $this->assertTrue($result['success']);
        
        $this->tenant->refresh();
        $this->assertEquals($this->planBasico->id, $this->tenant->plan_id);

        // Verificar se from_plan_id é null
        $migration = PlanMigration::where('tenant_id', $this->tenant->id)
            ->where('to_plan_id', $this->planBasico->id)
            ->first();

        $this->assertNull($migration->from_plan_id);
    }

    public function test_migrate_to_free_plan_activates_expired_tenant(): void
    {
        $this->tenant->update([
            'plan_id' => $this->planBasico->id,
            'account_status' => 'expired',
            'trial_started_at' => now()->subDays(10),
            'trial_expires_at' => now()->subDay(),
        ]);

        $result = $this->service->migratePlan(
            $this->tenant->id,
            $this->planGratis->id,
            'Downgrade para gratuito após expiração'
        );

        $this->assertTrue($result['success']);

        $this->tenant->refresh();
        $this->assertEquals($this->planGratis->id, $this->tenant->plan_id);
        $this->assertEquals('Grátis', $this->tenant->subscription_plan);
        $this->assertEquals('active', $this->tenant->account_status);
        $this->assertEquals(0, (float) $this->tenant->mrr);
        $this->assertNull($this->tenant->trial_expires_at);
    }

    public function test_migrate_to_paid_plan_activates_expired_tenant(): void
    {
        $this->tenant->update([
            'plan_id' => $this->planGratis->id,
            'account_status' => 'expired',
            'trial_started_at' => now()->subDays(10),
            'trial_expires_at' => now()->subDay(),
        ]);

        $result = $this->service->migratePlan(
            $this->tenant->id,
            $this->planBasico->id,
            'Admin atribuiu plano pago após trial expirado'
        );

        $this->assertTrue($result['success']);

        $this->tenant->refresh();
        $this->assertEquals($this->planBasico->id, $this->tenant->plan_id);
        $this->assertEquals('Básico', $this->tenant->subscription_plan);
        $this->assertEquals('active', $this->tenant->account_status);
        $this->assertEquals(49.90, (float) $this->tenant->mrr);
        $this->assertNull($this->tenant->trial_expires_at);
        $this->assertNull($this->tenant->trial_started_at);
        $this->assertTrue($this->tenant->canAccess());

        $status = $this->tenant->toTrialStatusArray();
        $this->assertFalse($status['is_expired']);
        $this->assertFalse($status['needs_payment']);
        $this->assertTrue($status['is_active']);
    }

    public function test_reactivate_same_free_plan_when_account_not_active(): void
    {
        $this->tenant->update([
            'plan_id' => $this->planGratis->id,
            'account_status' => 'expired',
        ]);

        $result = $this->service->migratePlan(
            $this->tenant->id,
            $this->planGratis->id
        );

        $this->assertTrue($result['success']);
        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
    }

    public function test_reactivate_same_paid_plan_when_account_not_active(): void
    {
        $this->tenant->update([
            'plan_id' => $this->planBasico->id,
            'account_status' => 'expired',
            'trial_expires_at' => now()->subDay(),
        ]);

        $result = $this->service->migratePlan(
            $this->tenant->id,
            $this->planBasico->id
        );

        $this->assertTrue($result['success']);
        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
        $this->assertNull($this->tenant->trial_expires_at);
        $this->assertTrue($this->tenant->canAccess());
    }

    public function test_get_migration_history_returns_correct_data(): void
    {
        // Criar algumas migrações
        PlanMigration::create([
            'tenant_id' => $this->tenant->id,
            'from_plan_id' => $this->planGratis->id,
            'to_plan_id' => $this->planBasico->id,
            'status' => 'completed',
            'migrated_at' => now()->subDays(5),
            'notes' => 'Primeira migração',
        ]);

        PlanMigration::create([
            'tenant_id' => $this->tenant->id,
            'from_plan_id' => $this->planBasico->id,
            'to_plan_id' => $this->planPremium->id,
            'status' => 'completed',
            'migrated_at' => now()->subDays(2),
            'notes' => 'Segunda migração',
        ]);

        $history = $this->service->getMigrationHistory($this->tenant->id);

        $this->assertCount(2, $history);
        $this->assertEquals('Premium', $history[0]['to_plan']); // Mais recente primeiro
        $this->assertEquals('Básico', $history[1]['to_plan']);
        $this->assertEquals('Segunda migração', $history[0]['notes']);
    }

    public function test_get_migration_history_respects_limit(): void
    {
        // Criar 15 migrações
        for ($i = 0; $i < 15; $i++) {
            PlanMigration::create([
                'tenant_id' => $this->tenant->id,
                'from_plan_id' => $this->planGratis->id,
                'to_plan_id' => $this->planBasico->id,
                'status' => 'completed',
                'migrated_at' => now()->subDays($i),
            ]);
        }

        $history = $this->service->getMigrationHistory($this->tenant->id, 10);

        $this->assertCount(10, $history);
    }

    public function test_migrate_plan_rolls_back_on_exception(): void
    {
        // Tentar migrar para plano inexistente
        try {
            $this->service->migratePlan($this->tenant->id, 99999);
        } catch (\Exception $e) {
            // Esperado
        }

        // Verificar que tenant não foi alterado
        $this->tenant->refresh();
        $this->assertEquals($this->planGratis->id, $this->tenant->plan_id);

        // Verificar que nenhuma migração foi criada
        $migrations = PlanMigration::where('tenant_id', $this->tenant->id)->count();
        $this->assertEquals(0, $migrations);
    }
}
