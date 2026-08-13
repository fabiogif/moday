<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\Batch;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc02StockAdjustmentTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc02_stock_adjustment_updates_batch_balance(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 50,
            'batch_number' => 'LOTE-ADJ',
        ])->assertStatus(201);

        $batch = Batch::where('batch_number', 'LOTE-ADJ')->first();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'     => 'ajuste',
            'batch_id' => $batch->id,
            'quantity' => -10,
            'notes'    => 'Quebra inventário',
        ])->assertStatus(201);

        $this->assertEquals(40, (float) $batch->fresh()->quantity_available);
        $this->assertEquals(40, (float) $product->fresh()->qtd_stock);

        $this->assertDatabaseHas('stock_movements', [
            'batch_id' => $batch->id,
            'type'     => 'ajuste',
            'quantity' => -10,
        ]);
    }
}
