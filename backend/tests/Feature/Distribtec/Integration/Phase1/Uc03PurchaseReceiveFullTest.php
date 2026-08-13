<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\Batch;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc03PurchaseReceiveFullTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc03_full_purchase_receive_creates_batch_and_updates_status(): void
    {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        $product  = $this->createProduct(['qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();

        $poResponse = $this->withHeaders($this->authHeaders())->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [[
                'product_id'       => $product->id,
                'quantity_ordered' => 200,
                'unit_cost'        => 5.00,
            ]],
        ])->assertStatus(201);

        $poId = $poResponse->json('data.id');
        $itemId = PurchaseOrder::find($poId)->items->first()->id;

        $this->advancePurchaseToConfirmed($poId);

        $this->withHeaders($this->authHeaders())->postJson("/api/purchase-orders/{$poId}/receive", [
            'items' => [[
                'purchase_order_item_id' => $itemId,
                'quantity'               => 200,
                'warehouse_id'           => $warehouse->id,
                'batch_number'           => 'LOTE-PC-001',
                'expiry_date'            => now()->addYear()->toDateString(),
            ]],
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'recebido');

        $this->assertDatabaseHas('batches', [
            'purchase_order_id' => $poId,
            'batch_number'      => 'LOTE-PC-001',
            'quantity_available'=> 200,
        ]);

        $this->assertEquals(200, (float) $product->fresh()->qtd_stock);
        $this->assertEquals(1, Batch::where('product_id', $product->id)->count());
    }
}
