<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Services\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PlanLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PlanLimitService $service;
    protected Tenant $tenant;
    protected Plan $planGratis;
    protected Plan $planBasico;
    protected Plan $planPremium;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PlanLimitService();

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

    public function test_check_limits_returns_false_when_no_limits_reached(): void
    {
        $result = $this->service->checkLimits($this->tenant->id);

        $this->assertFalse($result['has_limit_reached']);
        $this->assertEmpty($result['reached_limits']);
        $this->assertEquals('Grátis', $result['plan_name']);
    }

    public function test_check_limits_detects_user_limit_reached(): void
    {
        // Criar 1 usuário ativo (limite do plano Grátis)
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $result = $this->service->checkLimits($this->tenant->id);

        $this->assertTrue($result['has_limit_reached']);
        $this->assertContains('users', $result['reached_limits']);
        $this->assertEquals(1, $result['current_usage']['users']);
        $this->assertEquals(1, $result['plan_limits']['max_users']);
    }

    public function test_check_limits_detects_product_limit_reached(): void
    {
        // Criar 50 produtos (limite do plano Grátis)
        Product::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $result = $this->service->checkLimits($this->tenant->id);

        $this->assertTrue($result['has_limit_reached']);
        $this->assertContains('products', $result['reached_limits']);
        $this->assertEquals(50, $result['current_usage']['products']);
        $this->assertEquals(50, $result['plan_limits']['max_products']);
    }

    public function test_check_limits_detects_order_limit_reached(): void
    {
        // Criar 30 pedidos no mês atual (limite do plano Grátis)
        Order::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        $result = $this->service->checkLimits($this->tenant->id);

        $this->assertTrue($result['has_limit_reached']);
        $this->assertContains('orders', $result['reached_limits']);
        $this->assertEquals(30, $result['current_usage']['orders_this_month']);
        $this->assertEquals(30, $result['plan_limits']['max_orders_per_month']);
    }

    public function test_check_limits_detects_multiple_limits_reached(): void
    {
        // Criar 1 usuário, 50 produtos e 30 pedidos
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Product::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Order::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        $result = $this->service->checkLimits($this->tenant->id);

        $this->assertTrue($result['has_limit_reached']);
        $this->assertCount(3, $result['reached_limits']);
        $this->assertContains('users', $result['reached_limits']);
        $this->assertContains('products', $result['reached_limits']);
        $this->assertContains('orders', $result['reached_limits']);
    }

    public function test_check_limits_premium_has_no_limits(): void
    {
        // Atualizar tenant para plano Premium
        $this->tenant->update(['plan_id' => $this->planPremium->id]);

        // Criar muitos recursos
        User::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Product::factory()->count(200)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Order::factory()->count(500)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        $result = $this->service->checkLimits($this->tenant->id);

        $this->assertFalse($result['has_limit_reached']);
        $this->assertEmpty($result['reached_limits']);
    }

    public function test_get_current_usage_returns_correct_counts(): void
    {
        // Criar recursos
        User::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false, // Inativo não conta
        ]);

        Product::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false, // Inativo não conta
        ]);

        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subMonth(), // Mês passado não conta
        ]);

        $usage = $this->service->getCurrentUsage($this->tenant->id);

        $this->assertEquals(2, $usage['users']);
        $this->assertEquals(10, $usage['products']);
        $this->assertEquals(5, $usage['orders_this_month']);
    }

    public function test_check_user_limit_returns_correct_data(): void
    {
        // Criar 1 usuário (limite do plano Grátis)
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $result = $this->service->checkUserLimit($this->tenant->id);

        $this->assertTrue($result['reached']);
        $this->assertEquals(1, $result['current']);
        $this->assertEquals(1, $result['max']);
    }

    public function test_check_order_limit_returns_correct_data(): void
    {
        // Criar 30 pedidos (limite do plano Grátis)
        Order::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        $result = $this->service->checkOrderLimit($this->tenant->id);

        $this->assertTrue($result['reached']);
        $this->assertEquals(30, $result['current']);
        $this->assertEquals(30, $result['max']);
    }

    public function test_check_product_limit_returns_correct_data(): void
    {
        // Criar 50 produtos (limite do plano Grátis)
        Product::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $result = $this->service->checkProductLimit($this->tenant->id);

        $this->assertTrue($result['reached']);
        $this->assertEquals(50, $result['current']);
        $this->assertEquals(50, $result['max']);
    }

    public function test_check_limits_handles_tenant_without_plan(): void
    {
        // Remover plano do tenant
        $this->tenant->update(['plan_id' => null]);

        $result = $this->service->checkLimits($this->tenant->id);

        $this->assertEquals('Grátis', $result['plan_name']);
        $this->assertEquals(1, $result['plan_limits']['max_users']);
        $this->assertEquals(50, $result['plan_limits']['max_products']);
        $this->assertEquals(30, $result['plan_limits']['max_orders_per_month']);
    }

    public function test_check_limits_handles_nonexistent_tenant(): void
    {
        $result = $this->service->checkLimits(99999);

        $this->assertFalse($result['has_limit_reached']);
        $this->assertNull($result['plan_name']);
        $this->assertEmpty($result['current_usage']);
    }
}
