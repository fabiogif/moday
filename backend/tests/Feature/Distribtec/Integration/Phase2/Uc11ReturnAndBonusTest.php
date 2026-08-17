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
class Uc11ReturnAndBonusTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc11_bonus_item_has_zero_price(): void
    {
        $product = $this->createProduct(['price' => 50]);

        $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 1,
                'item_type'  => 'bonificacao',
            ]],
        ])->assertStatus(201)
            ->assertJsonPath('data.total', '0.00');
    }

    #[Test]
    public function uc11_return_restocks_and_adjusts_ar(): void
    {
        $product   = $this->createProduct(['price' => 100, 'qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();
        $client    = Client::factory()->create(['tenant_id' => $this->tenant->id, 'credit_limit' => 99999]);

        $batch = Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $product->id,
            'warehouse_id'       => $warehouse->id,
            'quantity_available' => 0,
            'quantity_sold'      => 5,
            'status'             => 'available',
        ]);

        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status'    => 'faturado',
            'total'     => 500,
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'batch_id'   => $batch->id,
            'quantity'   => 5,
            'unit_price' => 100,
            'subtotal'   => 500,
        ]);

        AccountReceivable::create([
            'tenant_id'     => $this->tenant->id,
            'client_id'     => $client->id,
            'sale_order_id' => $order->id,
            'description'   => 'AR teste',
            'issue_date'    => now(),
            'due_date'      => now()->addDays(30),
            'amount'        => 500,
            'status'        => 'pending',
        ]);

        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$order->id}/return", [
            'items' => [[
                'sale_order_item_id' => $item->id,
                'quantity'           => 2,
                'warehouse_id'       => $warehouse->id,
            ]],
        ])->assertStatus(200);

        $this->assertGreaterThan(0, (float) $product->fresh()->qtd_stock);

        $ar = AccountReceivable::where('sale_order_id', $order->id)->first();
        $this->assertEquals(300, (float) $ar->amount);
    }
}
