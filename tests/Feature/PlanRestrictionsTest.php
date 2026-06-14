<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class PlanRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $planGratis;
    protected $planBasico;
    protected $planPremium;

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
            'max_orders_per_month' => null, // Ilimitado
            'has_marketing' => true,
            'has_reports' => true,
        ]);

        // Criar tenant e usuário
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $this->planGratis->id,
            'subscription_plan' => 'Grátis',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Testa acesso negado a Reports para plano Grátis
     */
    public function test_gratis_plan_denies_reports_access()
    {
        $this->tenant->plan_id = $this->planGratis->id;
        $this->tenant->save();
        $this->tenant->refresh();

        $response = $this->actingAs($this->user, 'api')->getJson('/api/reports');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'PLAN_FEATURE_NOT_AVAILABLE',
                ])
                ->assertJsonPath('current_plan', 'Grátis')
                ->assertJsonPath('feature', 'Relatórios');
    }

    /**
     * Testa acesso permitido a Reports para plano Básico
     */
    public function test_basico_plan_allows_reports_access()
    {
        $this->tenant->plan_id = $this->planBasico->id;
        $this->tenant->subscription_plan = 'Básico';
        $this->tenant->save();
        $this->tenant->refresh();

        $response = $this->actingAs($this->user, 'api')->getJson('/api/reports');

        $response->assertStatus(200);
    }

    /**
     * Testa acesso permitido a Reports para plano Premium
     */
    public function test_premium_plan_allows_reports_access()
    {
        $this->tenant->plan_id = $this->planPremium->id;
        $this->tenant->subscription_plan = 'Premium';
        $this->tenant->save();
        $this->tenant->refresh();

        $response = $this->actingAs($this->user, 'api')->getJson('/api/reports');

        $response->assertStatus(200);
    }

    /**
     * Testa limite de 30 pedidos para plano Grátis
     */
    public function test_gratis_plan_limits_to_30_orders_per_month()
    {
        $this->tenant->plan_id = $this->planGratis->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Criar 30 pedidos do mês atual
        Order::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        // Criar produto e payment method para o teste
        $product = \App\Models\Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $paymentMethod = \App\Models\PaymentMethod::factory()->create(['tenant_id' => $this->tenant->id]);

        // Tentar criar o 31º pedido
        $response = $this->actingAs($this->user, 'api')->postJson('/api/order', [
            'token_company' => $this->tenant->uuid,
            'products' => [
                [
                    'identify' => $product->uuid,
                    'qty' => 1,
                    'price' => 10.00,
                ]
            ],
            'payment_method_id' => $paymentMethod->uuid,
            'is_delivery' => false,
            'table' => null, // Mesa será validada pelo OrderService
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'ORDER_LIMIT_REACHED',
                ])
                ->assertJsonPath('max_orders', 30)
                ->assertJsonPath('current_orders', 30);
    }

    /**
     * Testa limite de 100 pedidos para plano Básico
     */
    public function test_basico_plan_limits_to_100_orders_per_month()
    {
        $this->tenant->plan_id = $this->planBasico->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Criar 100 pedidos do mês atual
        Order::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        // Criar produto e payment method para o teste
        $product = \App\Models\Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $paymentMethod = \App\Models\PaymentMethod::factory()->create(['tenant_id' => $this->tenant->id]);

        // Tentar criar o 101º pedido
        $response = $this->actingAs($this->user, 'api')->postJson('/api/order', [
            'token_company' => $this->tenant->uuid,
            'products' => [
                [
                    'identify' => $product->uuid,
                    'qty' => 1,
                    'price' => 10.00,
                ]
            ],
            'payment_method_id' => $paymentMethod->uuid,
            'is_delivery' => false,
            'table' => null,
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'ORDER_LIMIT_REACHED',
                ])
                ->assertJsonPath('max_orders', 100)
                ->assertJsonPath('current_orders', 100);
    }

    /**
     * Testa que plano Premium não tem limite de pedidos
     */
    public function test_premium_plan_has_unlimited_orders()
    {
        $this->tenant->plan_id = $this->planPremium->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Criar 200 pedidos (mais que qualquer outro plano)
        Order::factory()->count(200)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        // Criar produto e payment method para o teste
        $product = \App\Models\Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $paymentMethod = \App\Models\PaymentMethod::factory()->create(['tenant_id' => $this->tenant->id]);

        // Tentar criar mais um pedido - deve passar o middleware (não deve retornar 403 de limite)
        $response = $this->actingAs($this->user, 'api')->postJson('/api/order', [
            'token_company' => $this->tenant->uuid,
            'products' => [
                [
                    'identify' => $product->uuid,
                    'qty' => 1,
                    'price' => 10.00,
                ]
            ],
            'payment_method_id' => $paymentMethod->uuid,
            'is_delivery' => false,
            'table' => null,
        ]);

        // Deve passar o middleware (não deve retornar 403 de ORDER_LIMIT_REACHED)
        // Pode retornar 422 se faltar dados (mesa, etc), mas não 403 de ORDER_LIMIT_REACHED
        $this->assertNotEquals(403, $response->status(), 'Premium não deve ter limite de pedidos');
    }

    /**
     * Testa que pedidos de meses anteriores não contam para o limite
     */
    public function test_orders_from_previous_months_do_not_count()
    {
        $this->tenant->plan_id = $this->planGratis->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Criar 30 pedidos do mês passado
        Order::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subMonth(),
        ]);

        // Criar 29 pedidos do mês atual
        Order::factory()->count(29)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        // Criar produto e payment method para o teste
        $product = \App\Models\Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $paymentMethod = \App\Models\PaymentMethod::factory()->create(['tenant_id' => $this->tenant->id]);

        // Tentar criar mais um pedido - deve passar o middleware (não deve retornar 403 de limite)
        $response = $this->actingAs($this->user, 'api')->postJson('/api/order', [
            'token_company' => $this->tenant->uuid,
            'products' => [
                [
                    'identify' => $product->uuid,
                    'qty' => 1,
                    'price' => 10.00,
                ]
            ],
            'payment_method_id' => $paymentMethod->uuid,
            'is_delivery' => false,
            'table' => null,
        ]);

        // Deve passar o middleware porque só tem 29 pedidos no mês atual
        // Pode retornar 422 se faltar dados (mesa, etc), mas não 403 de ORDER_LIMIT_REACHED
        $this->assertNotEquals(403, $response->status(), 'Não deve bloquear pedido quando ainda não atingiu limite');
    }

    /**
     * Testa que tenant com plano Grátis não tem acesso a features restritas
     */
    public function test_gratis_plan_does_not_have_premium_features()
    {
        $this->tenant->plan_id = $this->planGratis->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Tentar acessar Reports - deve negar
        $response = $this->actingAs($this->user, 'api')->getJson('/api/reports');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'PLAN_FEATURE_NOT_AVAILABLE',
                    'current_plan' => 'Grátis',
                ]);
    }

    /**
     * Testa limite de 1 usuário para plano Grátis
     */
    public function test_gratis_plan_limits_to_1_user()
    {
        $this->tenant->plan_id = $this->planGratis->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Já existe 1 usuário (criado no setUp)
        $this->assertEquals(1, User::where('tenant_id', $this->tenant->id)->where('is_active', true)->count());

        // Tentar criar 2º usuário
        $response = $this->actingAs($this->user, 'api')->postJson('/api/user', [
            'name' => 'Segundo Usuario',
            'email' => 'segundo@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'USER_LIMIT_REACHED',
                ])
                ->assertJsonPath('max_users', 1)
                ->assertJsonPath('current_users', 1);
    }

    /**
     * Testa limite de 5 usuários para plano Básico
     */
    public function test_basico_plan_limits_to_5_users()
    {
        $this->tenant->plan_id = $this->planBasico->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Criar 4 usuários adicionais (total 5 com o do setUp)
        User::factory()->count(4)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $this->assertEquals(5, User::where('tenant_id', $this->tenant->id)->where('is_active', true)->count());

        // Tentar criar 6º usuário
        $response = $this->actingAs($this->user, 'api')->postJson('/api/user', [
            'name' => 'Sexto Usuario',
            'email' => 'sexto@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'error_code' => 'USER_LIMIT_REACHED',
                ])
                ->assertJsonPath('max_users', 5)
                ->assertJsonPath('current_users', 5);
    }

    /**
     * Testa que plano Premium não tem limite de usuários
     */
    public function test_premium_plan_has_unlimited_users()
    {
        $this->tenant->plan_id = $this->planPremium->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Criar 20 usuários
        User::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        // Tentar criar mais um usuário - deve funcionar
        $response = $this->actingAs($this->user, 'api')->postJson('/api/user', [
            'name' => 'Usuario 21',
            'email' => 'usuario21@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // Não deve retornar 403 de USER_LIMIT_REACHED
        if ($response->status() === 403) {
            $this->assertNotEquals('USER_LIMIT_REACHED', $response->json('error_code'));
        }
    }

    /**
     * Testa que usuários inativos não contam para o limite
     */
    public function test_inactive_users_do_not_count_towards_limit()
    {
        $this->tenant->plan_id = $this->planGratis->id;
        $this->tenant->save();
        $this->tenant->refresh();

        // Desativar o usuário existente
        $this->user->is_active = false;
        $this->user->save();

        $this->assertEquals(0, User::where('tenant_id', $this->tenant->id)->where('is_active', true)->count());

        // Tentar criar novo usuário - deve funcionar pois o anterior está inativo
        $response = $this->actingAs($this->user, 'api')->postJson('/api/user', [
            'name' => 'Novo Usuario',
            'email' => 'novo@test.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        // Não deve retornar 403 de USER_LIMIT_REACHED
        if ($response->status() === 403) {
            $this->assertNotEquals('USER_LIMIT_REACHED', $response->json('error_code'));
        }
    }
}

