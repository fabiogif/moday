<?php

namespace Tests\Feature\Distribtec;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WarehouseApiTest extends TestCase
{
    use GrantsDistribtecPermissions;
    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan         = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $this->token  = JWTAuth::fromUser($this->user);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function guest_cannot_list_warehouses(): void
    {
        $this->getJson('/api/warehouses')->assertStatus(401);
    }

    #[Test]
    public function it_lists_only_tenant_warehouses(): void
    {
        Warehouse::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $otherPlan   = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson('/api/warehouses')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_creates_a_warehouse(): void
    {
        $payload = [
            'name'      => 'Armazém Central',
            'type'      => 'ambient',
            'city'      => 'São Paulo',
            'state'     => 'SP',
            'is_active' => true,
        ];

        $this->withHeaders($this->auth())
            ->postJson('/api/warehouses', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Armazém Central')
            ->assertJsonPath('data.type', 'ambient');

        $this->assertDatabaseHas('warehouses', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Armazém Central',
        ]);
    }

    #[Test]
    public function it_requires_name_to_create_warehouse(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/api/warehouses', ['type' => 'ambient'])
            ->assertStatus(422);
    }

    #[Test]
    public function it_shows_a_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->getJson("/api/warehouses/{$warehouse->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $warehouse->id);
    }

    #[Test]
    public function it_returns_404_for_another_tenants_warehouse(): void
    {
        $otherPlan   = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        $warehouse   = Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson("/api/warehouses/{$warehouse->id}")
            ->assertStatus(404);
    }

    #[Test]
    public function it_updates_a_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->putJson("/api/warehouses/{$warehouse->id}", ['name' => 'Novo Nome'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Novo Nome');
    }

    #[Test]
    public function it_deactivates_a_warehouse_on_delete(): void
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/warehouses/{$warehouse->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'is_active' => false]);
    }

    #[Test]
    public function type_must_be_valid(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/api/warehouses', ['name' => 'Test', 'type' => 'invalid_type'])
            ->assertStatus(422);
    }
}
