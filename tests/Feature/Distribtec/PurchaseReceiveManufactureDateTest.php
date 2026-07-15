<?php

namespace Tests\Feature\Distribtec;

use App\Models\Batch;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

class PurchaseReceiveManufactureDateTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function receive_persists_manufacture_date_on_batch(): void
    {
        $supplier  = Supplier::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        $product   = $this->createProduct(['qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();

        $poId = $this->withHeaders($this->authHeaders())->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items'       => [[
                'product_id'       => $product->id,
                'quantity_ordered' => 50,
                'unit_cost'        => 4.5,
            ]],
        ])->assertStatus(201)->json('data.id');

        $itemId = PurchaseOrder::find($poId)->items->first()->id;
        $this->advancePurchaseToConfirmed($poId);

        $this->withHeaders($this->authHeaders())->postJson("/api/purchase-orders/{$poId}/receive", [
            'items' => [[
                'purchase_order_item_id' => $itemId,
                'quantity'               => 50,
                'warehouse_id'           => $warehouse->id,
                'batch_number'           => 'PC-FAB-01',
                'manufacture_date'       => '2026-02-01',
                'expiry_date'            => '2027-02-01',
            ]],
        ])->assertStatus(200);

        $batch = Batch::where('batch_number', 'PC-FAB-01')->first();
        $this->assertNotNull($batch);
        $this->assertEquals($poId, $batch->purchase_order_id);
        $this->assertEquals('2026-02-01', $batch->manufacture_date?->toDateString());
        $this->assertEquals('2027-02-01', $batch->expiry_date?->toDateString());
        $this->assertEquals(50.0, (float) $batch->quantity_available);
    }

    #[Test]
    public function receive_allows_missing_expiry_date(): void
    {
        $supplier  = Supplier::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        $product   = $this->createProduct(['qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();

        $poId = $this->withHeaders($this->authHeaders())->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items'       => [[
                'product_id'       => $product->id,
                'quantity_ordered' => 20,
                'unit_cost'        => 3,
            ]],
        ])->assertStatus(201)->json('data.id');

        $itemId = PurchaseOrder::find($poId)->items->first()->id;
        $this->advancePurchaseToConfirmed($poId);

        $this->withHeaders($this->authHeaders())->postJson("/api/purchase-orders/{$poId}/receive", [
            'items' => [[
                'purchase_order_item_id' => $itemId,
                'quantity'               => 20,
                'warehouse_id'           => $warehouse->id,
                'batch_number'           => 'PC-NO-EXP',
            ]],
        ])->assertStatus(200);

        $batch = Batch::where('batch_number', 'PC-NO-EXP')->first();
        $this->assertNotNull($batch);
        $this->assertNull($batch->expiry_date);
    }

    #[Test]
    public function receive_controlled_product_requires_manufacture_date(): void
    {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        $product  = $this->createProduct([
            'qtd_stock'            => 0,
            'controlled_substance' => true,
        ]);
        $warehouse = $this->createWarehouse();

        $poId = $this->withHeaders($this->authHeaders())->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items'       => [[
                'product_id'       => $product->id,
                'quantity_ordered' => 10,
                'unit_cost'        => 9,
            ]],
        ])->assertStatus(201)->json('data.id');

        $itemId = PurchaseOrder::find($poId)->items->first()->id;
        $this->advancePurchaseToConfirmed($poId);

        $this->withHeaders($this->authHeaders())->postJson("/api/purchase-orders/{$poId}/receive", [
            'items' => [[
                'purchase_order_item_id' => $itemId,
                'quantity'               => 10,
                'warehouse_id'           => $warehouse->id,
                'batch_number'           => 'PC-CTRL',
                'expiry_date'            => now()->addYear()->toDateString(),
            ]],
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'A data de fabricação é obrigatória para produtos controlados ou que exigem receita.',
            ]);
    }
}
