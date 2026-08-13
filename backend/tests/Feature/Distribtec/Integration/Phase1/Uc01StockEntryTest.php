<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\Batch;
use App\Models\StockMovement;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc01StockEntryTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc01_manual_stock_entry_creates_batch_and_movement(): void
    {
        $product   = $this->createProduct(['qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 100,
            'batch_number' => 'LOTE-UC01',
            'expiry_date'  => now()->addMonths(6)->toDateString(),
            'unit_cost'    => 10.50,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('batches', [
            'tenant_id'    => $this->tenant->id,
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => 'LOTE-UC01',
        ]);

        $batch = Batch::where('batch_number', 'LOTE-UC01')->first();
        $this->assertEquals(100, (float) $batch->quantity_available);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id'  => $this->tenant->id,
            'product_id' => $product->id,
            'batch_id'   => $batch->id,
            'type'       => 'entrada',
        ]);

        $this->assertEquals(100, (float) $product->fresh()->qtd_stock);
        $this->assertEquals(1, StockMovement::where('type', 'entrada')->count());
    }
}
