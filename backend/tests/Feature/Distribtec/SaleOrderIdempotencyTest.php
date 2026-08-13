<?php

namespace Tests\Feature\Distribtec;

use App\Models\Plan;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SaleOrderIdempotencyTest extends TestCase
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
    public function resending_the_same_offline_id_does_not_duplicate_the_order(): void
    {
        $payload = [
            'offline_id'        => 'mobile-uuid-1234',
            'status'            => 'orcamento',
            'payment_term_days' => 30,
            'payment_method'    => 'boleto',
            'notes'             => 'Pedido criado offline',
        ];

        $first = $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders', $payload);
        $first->assertStatus(201);

        $second = $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders', $payload);
        $second->assertStatus(201);

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(
            1,
            SaleOrder::where('tenant_id', $this->tenant->id)
                ->where('offline_id', 'mobile-uuid-1234')
                ->count()
        );
    }

    #[Test]
    public function the_same_offline_id_in_different_tenants_creates_two_distinct_orders(): void
    {
        // Exercised at the service layer (not via two HTTP calls in one test) because this
        // test environment's JWT guard resolves the first authenticated user for any
        // subsequent request within the same test method, regardless of the bearer token
        // sent - a test-harness quirk, not an application bug. The tenant-scoped dedup
        // logic itself lives entirely in SaleOrderService::create()/SaleOrderRepository,
        // so calling it directly for two tenants is an equally valid (and more precise)
        // way to verify the unique index is scoped by tenant_id.
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => Plan::factory()->create()->id]);
        $otherUser   = User::factory()->create(['tenant_id' => $otherTenant->id]);

        $service = app(\App\Services\Sale\SaleOrderService::class);
        $data    = ['offline_id' => 'shared-uuid-9999', 'status' => 'orcamento', 'payment_term_days' => 30];

        $order1 = $service->create($this->tenant->id, $this->user->id, $data, []);
        $order2 = $service->create($otherTenant->id, $otherUser->id, $data, []);

        $this->assertNotSame($order1->id, $order2->id);
        $this->assertSame(
            2,
            SaleOrder::where('offline_id', 'shared-uuid-9999')->count()
        );
    }

    #[Test]
    public function an_order_without_offline_id_is_created_normally(): void
    {
        $payload = [
            'status'            => 'orcamento',
            'payment_term_days' => 30,
        ];

        $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('sale_orders', [
            'tenant_id'  => $this->tenant->id,
            'offline_id' => null,
        ]);
    }
}
