<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc04PurchaseReceivePartialTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc04_partial_receive_keeps_status_confirmado(): void
    {
        $supplier  = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $poResponse = $this->withHeaders($this->authHeaders())->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [[
                'product_id'       => $product->id,
                'quantity_ordered' => 1000,
                'unit_cost'        => 3.00,
            ]],
        ])->assertStatus(201);

        $poId   = $poResponse->json('data.id');
        $itemId = PurchaseOrder::find($poId)->items->first()->id;

        $this->advancePurchaseToConfirmed($poId);

        $this->withHeaders($this->authHeaders())->postJson("/api/purchase-orders/{$poId}/receive", [
            'items' => [[
                'purchase_order_item_id' => $itemId,
                'quantity'               => 500,
                'warehouse_id'           => $warehouse->id,
                'batch_number'           => 'LOTE-PARCIAL',
            ]],
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmado');

        $item = PurchaseOrder::find($poId)->items->first();
        $this->assertEquals(500, (float) $item->quantity_received);
        $this->assertEquals(500, (float) $product->fresh()->qtd_stock);
    }
}
