<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Plan $planGratis;
    private Plan $planBasico;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    private function registerPayload(int $planId, string $suffix): array
    {
        return [
            'company_name' => "Empresa Teste {$suffix}",
            'company_email' => "empresa{$suffix}@test.com",
            'company_cnpj' => sprintf('%014d', random_int(10000000000000, 99999999999998)),
            'name' => "Admin {$suffix}",
            'email' => "admin{$suffix}@test.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_id' => $planId,
        ];
    }

    public function test_free_plan_registration_activates_permanently_without_trial(): void
    {
        $response = $this->postJson('/api/register', $this->registerPayload($this->planGratis->id, 'free'));

        $response->assertStatus(201)
            ->assertJsonPath('data.trial_status.is_trial', false)
            ->assertJsonPath('data.trial_status.is_active', true)
            ->assertJsonPath('data.trial_status.needs_payment', false);

        $tenant = Tenant::where('email', 'empresafree@test.com')->first();

        $this->assertNotNull($tenant);
        $this->assertEquals('active', $tenant->account_status);
        $this->assertNull($tenant->trial_started_at);
        $this->assertNull($tenant->trial_expires_at);
        $this->assertTrue($tenant->canAccess());
    }

    public function test_paid_plan_registration_starts_seven_day_trial(): void
    {
        $response = $this->postJson('/api/register', $this->registerPayload($this->planBasico->id, 'paid'));

        $response->assertStatus(201)
            ->assertJsonPath('data.trial_status.is_trial', true)
            ->assertJsonPath('data.trial_status.is_active', true)
            ->assertJsonPath('data.trial_status.needs_payment', true);

        $tenant = Tenant::where('email', 'empresapaid@test.com')->first();

        $this->assertNotNull($tenant);
        $this->assertEquals('trial', $tenant->account_status);
        $this->assertNotNull($tenant->trial_started_at);
        $this->assertNotNull($tenant->trial_expires_at);
        $this->assertEquals(
            now()->addDays(7)->format('Y-m-d'),
            $tenant->trial_expires_at->format('Y-m-d')
        );
        $this->assertTrue($tenant->canAccess());
    }

    public function test_expired_paid_trial_is_blocked_by_middleware(): void
    {
        $tenant = Tenant::factory()->create([
            'plan_id' => $this->planBasico->id,
            'account_status' => 'trial',
            'trial_started_at' => now()->subDays(10),
            'trial_expires_at' => now()->subDay(),
            'subscription_plan' => 'Básico',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $this->assertFalse($tenant->fresh()->canAccess());

        $response = $this->actingAs($user, 'api')->getJson('/api/product');

        $response->assertStatus(403)
            ->assertJsonPath('error', 'trial_expired');
    }

    public function test_registered_owner_can_list_users_after_signup(): void
    {
        $response = $this->postJson('/api/register', $this->registerPayload($this->planBasico->id, 'acl'));

        $response->assertStatus(201);

        $user = User::where('email', 'adminacl@test.com')->firstOrFail();

        $this->assertTrue($user->hasPermissionTo('users.index'));

        $this->actingAs($user, 'api')
            ->getJson('/api/users')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_free_plan_tenant_is_not_blocked_by_trial_middleware(): void
    {
        $tenant = Tenant::factory()->create([
            'plan_id' => $this->planGratis->id,
            'account_status' => 'active',
            'subscription_plan' => 'Grátis',
            'activated_at' => now(),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $this->assertTrue($tenant->fresh()->canAccess());

        $response = $this->actingAs($user, 'api')->getJson('/api/product');

        $this->assertNotEquals(403, $response->status());
    }
}
