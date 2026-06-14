<?php

namespace Tests\Feature\Distribtec\Integration\Phase3;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\StockReservation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc14RegulatoryRecallTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc14_controlled_product_requires_prescription_on_approval(): void
    {
        $product = $this->createProduct([
            'controlled_substance' => true,
            'qtd_stock'            => 0,
        ]);
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 10,
            'batch_number' => 'CTRL-001',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 2,
                'unit_price' => 50,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders())->putJson("/api/sale-orders/{$orderId}", [
            'prescription_verified' => true,
        ])->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'aprovado');
    }

    #[Test]
    public function uc14_recall_releases_reservations_and_logs_audit(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 20,
            'batch_number' => 'RECALL-001',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 6,
                'unit_price' => 10,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");

        $batch = Batch::where('batch_number', 'RECALL-001')->first();
        $this->assertEquals(6, (float) $batch->fresh()->quantity_reserved);
        $this->assertEquals(1, StockReservation::where('batch_id', $batch->id)->where('status', 'reserved')->count());

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/batches/{$batch->id}/recall", ['reason' => 'Lote suspeito'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'recalled');

        $this->assertEquals(0, (float) $batch->fresh()->quantity_reserved);
        $this->assertEquals(0, StockReservation::where('batch_id', $batch->id)->where('status', 'reserved')->count());

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'batch.recalled',
            'entity_type' => 'batch',
            'entity_id'   => $batch->id,
        ]);
    }

    #[Test]
    public function uc14_cold_chain_blocks_wrong_warehouse(): void
    {
        $product = $this->createProduct([
            'storage_temperature' => 'frozen',
            'qtd_stock'           => 0,
        ]);
        $ambientWarehouse = $this->createWarehouse(['type' => 'ambient']);

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $ambientWarehouse->id,
            'quantity'     => 10,
            'batch_number' => 'COLD-001',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 2,
                'unit_price' => 30,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(422);
    }
}
