<?php

namespace Tests\Feature\Distribtec;

use App\Models\Batch;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BatchApiTest extends TestCase
{
    use GrantsDistribtecPermissions;
    private User $user;
    private Tenant $tenant;
    private string $token;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $plan           = Plan::factory()->create();
        $this->tenant   = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user     = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $this->token    = JWTAuth::fromUser($this->user);
        $this->product  = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function guest_cannot_list_batches(): void
    {
        $this->getJson('/api/batches')->assertStatus(401);
    }

    #[Test]
    public function it_lists_only_tenant_batches(): void
    {
        Batch::factory()->count(3)->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
        ]);

        $otherPlan    = Plan::factory()->create();
        $otherTenant  = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        $otherProduct = Product::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherWH      = Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);
        Batch::factory()->create([
            'tenant_id'   => $otherTenant->id,
            'product_id'  => $otherProduct->id,
            'warehouse_id'=> $otherWH->id,
        ]);

        $this->withHeaders($this->auth())
            ->getJson('/api/batches')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_lists_expiring_batches(): void
    {
        // Expiring in 15 days — should appear
        Batch::factory()->expiringSoon(15)->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'status'      => 'available',
            'quantity_available' => 10,
        ]);

        // Expiring in 90 days — should NOT appear with default 60d window
        Batch::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'expiry_date' => now()->addDays(90),
            'status'      => 'available',
            'quantity_available' => 10,
        ]);

        $this->withHeaders($this->auth())
            ->getJson('/api/batches/expiring?days=60')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_lists_expired_batches(): void
    {
        Batch::factory()->expired()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
        ]);

        // Not expired
        Batch::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'expiry_date' => now()->addDays(60),
        ]);

        $this->withHeaders($this->auth())
            ->getJson('/api/batches/expired')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_shows_a_batch_with_days_until_expiry(): void
    {
        $batch = Batch::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'expiry_date' => now()->addDays(30),
        ]);

        $response = $this->withHeaders($this->auth())
            ->getJson("/api/batches/{$batch->id}")
            ->assertStatus(200);

        $this->assertNotNull($response->json('data.days_until_expiry'));
    }

    #[Test]
    public function it_returns_404_for_another_tenants_batch(): void
    {
        $otherPlan   = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        $product     = Product::factory()->create(['tenant_id' => $otherTenant->id]);
        $warehouse   = Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);
        $batch       = Batch::factory()->create([
            'tenant_id'   => $otherTenant->id,
            'product_id'  => $product->id,
            'warehouse_id'=> $warehouse->id,
        ]);

        $this->withHeaders($this->auth())
            ->getJson("/api/batches/{$batch->id}")
            ->assertStatus(404);
    }

    #[Test]
    public function response_includes_days_until_expiry_field(): void
    {
        Batch::factory()->expiringSoon(10)->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'status'      => 'available',
            'quantity_available' => 5,
        ]);

        $response = $this->withHeaders($this->auth())
            ->getJson('/api/batches/expiring')
            ->assertStatus(200);

        $this->assertArrayHasKey('days_until_expiry', $response->json('data.0'));
    }
}
