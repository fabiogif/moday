<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PlanLimitApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Plan $planGratis;
    protected Plan $planBasico;
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

    public function test_check_limits_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/plan/limits/check');

        $response->assertStatus(401);
    }

    public function test_check_limits_endpoint_returns_success(): void
    {
        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/plan/limits/check');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'has_limit_reached',
                        'reached_limits',
                        'current_usage' => [
                            'users',
                            'products',
                            'orders_this_month',
                        ],
                        'plan_limits' => [
                            'max_users',
                            'max_products',
                            'max_orders_per_month',
                        ],
                        'plan_name',
                        'message',
                    ],
                ]);
    }

    public function test_check_limits_endpoint_detects_limit_reached(): void
    {
        // Criar 1 usuário (atinge limite do plano Grátis)
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/plan/limits/check');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'has_limit_reached' => true,
                    ],
                ]);

        $data = $response->json('data');
        $this->assertContains('users', $data['reached_limits']);
    }

    public function test_get_current_usage_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/plan/current-usage');

        $response->assertStatus(401);
    }

    public function test_get_current_usage_endpoint_returns_correct_data(): void
    {
        // Criar recursos
        User::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Product::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/plan/current-usage');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'users' => 3, // 2 criados + 1 do setUp
                        'products' => 10,
                        'orders_this_month' => 5,
                    ],
                ]);
    }

    public function test_get_current_usage_only_counts_active_resources(): void
    {
        // Criar recursos ativos e inativos
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false, // Não deve contar
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false, // Não deve contar
        ]);

        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/plan/current-usage');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(2, $data['users']); // 1 criado + 1 do setUp
        $this->assertEquals(1, $data['products']);
    }

    public function test_get_current_usage_only_counts_current_month_orders(): void
    {
        // Criar pedidos do mês atual
        Order::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        // Criar pedidos do mês passado (não devem contar)
        Order::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subMonth(),
        ]);

        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/plan/current-usage');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(3, $data['orders_this_month']);
    }
}
