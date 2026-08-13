<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc03bSupplierHomologationTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc03b_blocks_confirm_when_supplier_inactive(): void
    {
        $supplier = Supplier::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'is_active'  => false,
        ]);
        $product = $this->createProduct();

        $poId = $this->withHeaders($this->authHeaders())->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items'       => [[
                'product_id'       => $product->id,
                'quantity_ordered' => 10,
                'unit_cost'        => 5,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())->postJson("/api/purchase-orders/{$poId}/advance-status");
        $this->withHeaders($this->authHeaders())
            ->postJson("/api/purchase-orders/{$poId}/advance-status")
            ->assertStatus(422);
    }

    #[Test]
    public function uc03b_blocks_receive_when_supplier_inactive(): void
    {
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $poId = $this->withHeaders($this->authHeaders())->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items'       => [[
                'product_id'       => $product->id,
                'quantity_ordered' => 10,
                'unit_cost'        => 5,
            ]],
        ])->json('data.id');

        $this->advancePurchaseToConfirmed($poId);

        $supplier->update(['is_active' => false]);
        $itemId = PurchaseOrder::find($poId)->items->first()->id;

        $this->withHeaders($this->authHeaders())->postJson("/api/purchase-orders/{$poId}/receive", [
            'items' => [[
                'purchase_order_item_id' => $itemId,
                'quantity'               => 5,
                'warehouse_id'           => $warehouse->id,
                'batch_number'           => 'BLOQ-FORN',
            ]],
        ])->assertStatus(422);
    }
}
