<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PlanMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PlanMigrationApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Plan $planGratis;
    protected Plan $planBasico;
    protected Plan $planPremium;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Criar tenant e usuário
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $this->planGratis->id,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_migrate_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/plan/migrate', [
            'plan_id' => $this->planBasico->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_migrate_endpoint_validates_plan_id_required(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/plan/migrate', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_migrate_endpoint_validates_plan_id_exists(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/plan/migrate', [
            'plan_id' => 99999,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_migrate_endpoint_successfully_migrates_plan(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/plan/migrate', [
            'plan_id' => $this->planBasico->id,
            'notes' => 'Migração de teste',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'plan_id',
                        'subscription_plan',
                    ],
                ]);

        // Verificar se tenant foi atualizado
        $this->tenant->refresh();
        $this->assertEquals($this->planBasico->id, $this->tenant->plan_id);
        $this->assertEquals('Básico', $this->tenant->subscription_plan);

        // Verificar se migração foi registrada
        $migration = PlanMigration::where('tenant_id', $this->tenant->id)
            ->where('to_plan_id', $this->planBasico->id)
            ->first();

        $this->assertNotNull($migration);
        $this->assertEquals('Migração de teste', $migration->notes);
    }

    public function test_migrate_endpoint_returns_error_if_plan_inactive(): void
    {
        // Desativar plano
        $this->planBasico->update(['is_active' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/plan/migrate', [
            'plan_id' => $this->planBasico->id,
        ]);

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                ]);
    }

    public function test_migrate_endpoint_returns_error_if_same_plan(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/plan/migrate', [
            'plan_id' => $this->planGratis->id,
        ]);

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                ]);
    }

    public function test_history_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/plan/migrations/history');

        $response->assertStatus(401);
    }

    public function test_history_endpoint_returns_migration_history(): void
    {
        // Criar migrações
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/plan/migrations/history');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'from_plan',
                            'to_plan',
                            'status',
                            'migrated_at',
                            'notes',
                        ],
                    ],
                ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Premium', $data[0]['to_plan']); // Mais recente primeiro
    }

    public function test_history_endpoint_returns_empty_array_when_no_migrations(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/plan/migrations/history');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [],
                ]);
    }

    public function test_migrate_endpoint_validates_notes_max_length(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/plan/migrate', [
            'plan_id' => $this->planBasico->id,
            'notes' => str_repeat('a', 501), // Mais de 500 caracteres
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['notes']);
    }
}
