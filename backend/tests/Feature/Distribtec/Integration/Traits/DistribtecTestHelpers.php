<?php

namespace Tests\Feature\Distribtec\Integration\Traits;

use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tymon\JWTAuth\Facades\JWTAuth;

trait DistribtecTestHelpers
{
    use GrantsDistribtecPermissions;
    protected User $user;
    protected Tenant $tenant;
    protected string $token;

    protected function setUpDistribtecTenant(): void
    {
        $plan         = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $this->token  = JWTAuth::fromUser($this->user);
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    protected function createProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['tenant_id' => $this->tenant->id], $attrs));
    }

    protected function createWarehouse(array $attrs = []): Warehouse
    {
        return Warehouse::factory()->create(array_merge(['tenant_id' => $this->tenant->id], $attrs));
    }

    protected function advancePurchaseToConfirmed(int $purchaseOrderId): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson("/api/purchase-orders/{$purchaseOrderId}/advance-status")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/purchase-orders/{$purchaseOrderId}/advance-status")
            ->assertStatus(200);
    }

    protected function confirmPickingForOrder(int $orderId): void
    {
        $order = \App\Models\SaleOrder::findOrFail($orderId);
        $items = $order->items->map(fn ($i) => [
            'sale_order_item_id' => $i->id,
            'quantity_picked'    => (float) $i->quantity,
        ])->all();

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/picking/confirm", ['items' => $items])
            ->assertStatus(200);
    }
}
