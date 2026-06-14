<?php

namespace Tests\Feature\Distribtec\Integration\Phase3;

use App\Models\Batch;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc15InventoryCountTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc15_inventory_count_adjusts_stock_on_close(): void
    {
        $product   = $this->createProduct(['qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 50,
            'batch_number' => 'INV-001',
        ]);

        $batch = Batch::where('batch_number', 'INV-001')->first();
        $this->assertEquals(50, (float) $batch->quantity_available);

        $countId = $this->withHeaders($this->authHeaders())->postJson('/api/inventory-counts', [
            'warehouse_id' => $warehouse->id,
            'notes'        => 'Contagem mensal',
        ])->assertStatus(201)->json('data.id');

        $this->withHeaders($this->authHeaders())->postJson("/api/inventory-counts/{$countId}/items", [
            'product_id'       => $product->id,
            'batch_id'         => $batch->id,
            'counted_quantity' => 48,
        ])->assertStatus(201);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/inventory-counts/{$countId}/close")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');

        $this->assertEquals(48, (float) $batch->fresh()->quantity_available);
        $this->assertEquals(48, (float) $product->fresh()->qtd_stock);

        $this->assertDatabaseHas('stock_movements', [
            'type'       => 'ajuste',
            'batch_id'   => $batch->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'inventory.closed',
            'entity_type' => 'inventory_count',
            'entity_id'   => $countId,
        ]);
    }
}
