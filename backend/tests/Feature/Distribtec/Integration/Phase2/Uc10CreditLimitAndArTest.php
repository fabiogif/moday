<?php

namespace Tests\Feature\Distribtec\Integration\Phase2;

use App\Models\AccountReceivable;
use App\Models\Batch;
use App\Models\Client;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\Warehouse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc10CreditLimitAndArTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc10_blocks_approval_when_credit_exceeded(): void
    {
        $product   = $this->createProduct(['price' => 500]);
        $warehouse = $this->createWarehouse();
        Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $product->id,
            'warehouse_id'       => $warehouse->id,
            'quantity_available' => 100,
            'status'             => 'available',
        ]);

        $client = Client::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'credit_limit' => 1000,
        ]);

        AccountReceivable::create([
            'tenant_id'  => $this->tenant->id,
            'client_id'  => $client->id,
            'description'=> 'Pendente',
            'issue_date' => now(),
            'due_date'   => now()->addDays(30),
            'amount'     => 900,
            'status'     => 'pending',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'client_id' => $client->id,
            'status'    => 'orcamento',
            'items'     => [[
                'product_id' => $product->id,
                'quantity'   => 1,
                'unit_price' => 500,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(422);
    }

    #[Test]
    public function uc10_creates_ar_on_billing(): void
    {
        $product   = $this->createProduct(['price' => 200]);
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 10,
            'batch_number' => 'LOTE-AR',
        ]);

        $client = Client::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'credit_limit' => 50000,
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'client_id' => $client->id,
            'status'    => 'orcamento',
            'items'     => [[
                'product_id' => $product->id,
                'quantity'   => 2,
                'unit_price' => 200,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");
        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");

        $this->confirmPickingForOrder($orderId);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'faturado');

        $this->assertDatabaseHas('accounts_receivable', [
            'sale_order_id' => $orderId,
            'client_id'     => $client->id,
            'amount'        => 400,
        ]);
    }
}
