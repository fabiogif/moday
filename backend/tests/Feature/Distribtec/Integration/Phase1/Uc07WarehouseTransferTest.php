<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\Batch;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc07WarehouseTransferTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc07_transfer_moves_stock_between_warehouses(): void
    {
        $product    = $this->createProduct();
        $warehouseA = $this->createWarehouse(['name' => 'Armazém A']);
        $warehouseB = $this->createWarehouse(['name' => 'Armazém B']);

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouseA->id,
            'quantity'     => 80,
            'batch_number' => 'LOTE-TRANSF',
        ])->assertStatus(201);

        $sourceBatch = Batch::where('batch_number', 'LOTE-TRANSF')->first();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'            => 'transferencia',
            'batch_id'        => $sourceBatch->id,
            'to_warehouse_id' => $warehouseB->id,
            'quantity'        => 30,
        ])->assertStatus(201);

        $this->assertEquals(50, (float) $sourceBatch->fresh()->quantity_available);

        $destBatch = Batch::where('warehouse_id', $warehouseB->id)
            ->where('batch_number', 'LOTE-TRANSF')
            ->first();

        $this->assertNotNull($destBatch);
        $this->assertEquals(30, (float) $destBatch->quantity_available);
        $this->assertEquals(80, (float) $product->fresh()->qtd_stock);
    }
}
