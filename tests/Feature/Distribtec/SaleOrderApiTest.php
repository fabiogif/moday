<?php

namespace Tests\Feature\Distribtec;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SaleOrderApiTest extends TestCase
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

    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_cannot_list_sale_orders(): void
    {
        $this->getJson('/api/sale-orders')->assertStatus(401);
    }

    #[Test]
    public function it_lists_only_tenant_sale_orders(): void
    {
        SaleOrder::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $otherPlan   = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        SaleOrder::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson('/api/sale-orders')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_does_not_list_archived_orders(): void
    {
        SaleOrder::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);
        SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'archived_at' => now()]);

        $this->withHeaders($this->auth())
            ->getJson('/api/sale-orders')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    #[Test]
    public function it_creates_a_sale_order_without_items(): void
    {
        $payload = [
            'status'            => 'orcamento',
            'payment_term_days' => 30,
            'payment_method'    => 'boleto',
            'notes'             => 'Teste',
        ];

        $response = $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'orcamento')
            ->assertJsonPath('data.payment_term_days', 30);

        $this->assertDatabaseHas('sale_orders', [
            'tenant_id' => $this->tenant->id,
            'status'    => 'orcamento',
        ]);
    }

    #[Test]
    public function it_creates_a_sale_order_with_items(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price'     => 50.00,
        ]);

        $payload = [
            'status'            => 'orcamento',
            'payment_term_days' => 30,
            'freight_amount'    => 10.00,
            'items' => [
                [
                    'product_id'       => $product->id,
                    'quantity'         => 2,
                    'unit_price'       => 50.00,
                    'discount_percent' => 0,
                ],
            ],
        ];

        $response = $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'orcamento')
            ->assertJsonPath('data.total', '110.00'); // 2×50 + 10 frete

        $this->assertDatabaseHas('sale_order_items', ['product_id' => $product->id]);
    }

    #[Test]
    public function it_calculates_discount_on_items(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 100.00]);

        $payload = [
            'items' => [
                [
                    'product_id'       => $product->id,
                    'quantity'         => 1,
                    'unit_price'       => 100.00,
                    'discount_percent' => 10, // 10% off → 90
                ],
            ],
        ];

        $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.total', '90.00');
    }

    // -------------------------------------------------------------------------
    // SHOW
    // -------------------------------------------------------------------------

    #[Test]
    public function it_shows_a_sale_order(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->getJson("/api/sale-orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    #[Test]
    public function it_returns_404_for_another_tenants_order(): void
    {
        $otherPlan   = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        $order       = SaleOrder::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson("/api/sale-orders/{$order->id}")
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    #[Test]
    public function it_updates_a_sale_order(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->putJson("/api/sale-orders/{$order->id}", ['notes' => 'Atualizado'])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', 'Atualizado');
    }

    // -------------------------------------------------------------------------
    // ADVANCE STATUS
    // -------------------------------------------------------------------------

    #[Test]
    public function it_advances_sale_order_status(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'orcamento']);

        $this->withHeaders($this->auth())
            ->postJson("/api/sale-orders/{$order->id}/advance-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'aprovado');
    }

    #[Test]
    public function it_cannot_advance_delivered_order(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'entregue']);

        $this->withHeaders($this->auth())
            ->postJson("/api/sale-orders/{$order->id}/advance-status")
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // CANCEL
    // -------------------------------------------------------------------------

    #[Test]
    public function it_cancels_a_sale_order(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'orcamento']);

        $this->withHeaders($this->auth())
            ->postJson("/api/sale-orders/{$order->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelado');
    }

    #[Test]
    public function it_cannot_cancel_delivered_order(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'entregue']);

        $this->withHeaders($this->auth())
            ->postJson("/api/sale-orders/{$order->id}/cancel")
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------

    #[Test]
    public function it_deletes_an_orcamento_order(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'orcamento']);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/sale-orders/{$order->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('sale_orders', ['id' => $order->id]);
    }

    #[Test]
    public function it_cannot_delete_an_approved_order(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'aprovado']);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/sale-orders/{$order->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('sale_orders', ['id' => $order->id]);
    }
}
