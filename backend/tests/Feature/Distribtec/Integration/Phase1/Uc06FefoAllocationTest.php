<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\Batch;
use App\Models\StockReservation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc06FefoAllocationTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc06_fefo_consumes_oldest_expiry_batch_first(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $product->id,
            'warehouse_id'       => $warehouse->id,
            'batch_number'       => 'LOTE-NOVO',
            'expiry_date'        => now()->addMonths(12),
            'quantity_received'  => 50,
            'quantity_available' => 50,
            'status'             => 'available',
        ]);

        Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $product->id,
            'warehouse_id'       => $warehouse->id,
            'batch_number'       => 'LOTE-ANTIGO',
            'expiry_date'        => now()->addMonths(3),
            'quantity_received'  => 50,
            'quantity_available' => 50,
            'status'             => 'available',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 60,
                'unit_price' => 10,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200);

        $oldBatch = Batch::where('batch_number', 'LOTE-ANTIGO')->first();
        $newBatch = Batch::where('batch_number', 'LOTE-NOVO')->first();

        $this->assertEquals(50, (float) $oldBatch->fresh()->quantity_reserved);
        $this->assertEquals(10, (float) $newBatch->fresh()->quantity_reserved);

        $reservations = StockReservation::where('sale_order_id', $orderId)->where('status', 'reserved')->get();
        $this->assertCount(2, $reservations);
    }
}
