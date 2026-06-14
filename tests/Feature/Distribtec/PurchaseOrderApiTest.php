<?php

namespace Tests\Feature\Distribtec;

use App\Models\Plan;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PurchaseOrderApiTest extends TestCase
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
    public function guest_cannot_list_purchase_orders(): void
    {
        $this->getJson('/api/purchase-orders')->assertStatus(401);
    }

    #[Test]
    public function it_lists_only_tenant_purchase_orders(): void
    {
        PurchaseOrder::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $otherPlan   = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        PurchaseOrder::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeaders($this->auth())
            ->getJson('/api/purchase-orders')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_creates_a_purchase_order(): void
    {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
        $product  = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $payload = [
            'supplier_id'       => $supplier->id,
            'expected_delivery' => now()->addDays(7)->format('Y-m-d'),
            'payment_term_days' => 30,
            'items' => [
                [
                    'product_id'       => $product->id,
                    'quantity_ordered'  => 10,
                    'unit_cost'        => 25.00,
                ],
            ],
        ];

        $response = $this->withHeaders($this->auth())
            ->postJson('/api/purchase-orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'rascunho')
            ->assertJsonPath('data.total', '250.00');

        $this->assertDatabaseHas('purchase_orders', [
            'tenant_id'   => $this->tenant->id,
            'supplier_id' => $supplier->id,
            'status'      => 'rascunho',
        ]);
        $this->assertDatabaseHas('purchase_order_items', ['product_id' => $product->id]);
    }

    #[Test]
    public function it_creates_purchase_order_without_supplier(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/api/purchase-orders', ['notes' => 'Sem fornecedor ainda'])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'rascunho');
    }

    #[Test]
    public function it_shows_a_purchase_order(): void
    {
        $order = PurchaseOrder::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->getJson("/api/purchase-orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    #[Test]
    public function it_updates_an_editable_purchase_order(): void
    {
        $order = PurchaseOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'rascunho']);

        $this->withHeaders($this->auth())
            ->putJson("/api/purchase-orders/{$order->id}", ['notes' => 'Nota atualizada'])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', 'Nota atualizada');
    }

    #[Test]
    public function it_cannot_update_a_confirmed_order(): void
    {
        $order = PurchaseOrder::factory()->confirmado()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->putJson("/api/purchase-orders/{$order->id}", ['notes' => 'Tentativa'])
            ->assertStatus(422);
    }

    #[Test]
    public function it_advances_status_from_rascunho_to_enviado(): void
    {
        $order = PurchaseOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'rascunho']);

        $this->withHeaders($this->auth())
            ->postJson("/api/purchase-orders/{$order->id}/advance-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'enviado');
    }

    #[Test]
    public function it_advances_status_from_enviado_to_confirmado(): void
    {
        $order = PurchaseOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'enviado']);

        $this->withHeaders($this->auth())
            ->postJson("/api/purchase-orders/{$order->id}/advance-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmado');
    }

    #[Test]
    public function it_cannot_advance_from_terminal_status(): void
    {
        $order = PurchaseOrder::factory()->recebido()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->postJson("/api/purchase-orders/{$order->id}/advance-status")
            ->assertStatus(422);
    }

    #[Test]
    public function it_deletes_a_draft_order(): void
    {
        $order = PurchaseOrder::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'rascunho']);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/purchase-orders/{$order->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('purchase_orders', ['id' => $order->id]);
    }

    #[Test]
    public function it_cannot_delete_a_confirmed_order(): void
    {
        $order = PurchaseOrder::factory()->confirmado()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/purchase-orders/{$order->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('purchase_orders', ['id' => $order->id]);
    }
}
