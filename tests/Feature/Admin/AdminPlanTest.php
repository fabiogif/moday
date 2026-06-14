<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPlanTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(?string $role = 'admin')
    {
        $admin = AdminUser::factory()->create(['role' => $role]);
        $token = $admin->createToken('test-token')->plainTextToken;

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    #[Test]
    public function it_lists_plans_for_authenticated_admin()
    {
        Plan::factory()->count(3)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'total'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_shows_single_plan()
    {
        $plan = Plan::factory()->create(['name' => 'Básico']);

        $response = $this->actingAsAdmin()
            ->getJson("/api/admin/plans/{$plan->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Básico');
    }

    #[Test]
    public function it_creates_plan()
    {
        $payload = [
            'name' => 'Enterprise',
            'url' => 'enterprise',
            'price' => 199.90,
            'description' => 'Plano enterprise',
            'is_active' => true,
            'max_users' => 50,
            'max_products' => 500,
            'max_orders_per_month' => 1000,
            'has_marketing' => true,
            'has_reports' => true,
            'details' => [
                ['name' => 'Suporte prioritário'],
            ],
        ];

        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/plans', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Enterprise');

        $this->assertDatabaseHas('plans', [
            'name' => 'Enterprise',
            'url' => 'enterprise',
        ]);
    }

    #[Test]
    public function it_updates_plan()
    {
        $plan = Plan::factory()->create(['name' => 'Antigo']);

        $response = $this->actingAsAdmin()
            ->putJson("/api/admin/plans/{$plan->id}", [
                'name' => 'Atualizado',
                'url' => $plan->url,
                'price' => $plan->price,
                'is_active' => true,
                'has_marketing' => false,
                'has_reports' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Atualizado');
    }

    #[Test]
    public function it_deletes_plan()
    {
        $plan = Plan::factory()->create();

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/admin/plans/{$plan->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    #[Test]
    public function it_denies_access_without_token()
    {
        $response = $this->getJson('/api/admin/plans');

        $response->assertStatus(401);
    }

    #[Test]
    public function analyst_cannot_manage_plans()
    {
        Plan::factory()->create();

        $response = $this->actingAsAdmin('analyst')
            ->getJson('/api/admin/plans');

        $response->assertStatus(403);
    }
}
