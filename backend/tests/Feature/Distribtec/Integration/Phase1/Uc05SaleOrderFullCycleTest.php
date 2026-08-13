<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\Batch;
use App\Models\SaleOrder;
use App\Models\StockReservation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc05SaleOrderFullCycleTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc05_sale_order_full_cycle_reserves_and_fulfills_stock(): void
    {
        $product   = $this->createProduct(['qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 30,
            'batch_number' => 'LOTE-VENDA',
        ])->assertStatus(201);

        $orderResponse = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 10,
                'unit_price' => 25.00,
            ]],
        ])->assertStatus(201);

        $orderId = $orderResponse->json('data.id');

        // orcamento -> aprovado (reserva)
        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'aprovado');

        $batch = Batch::where('batch_number', 'LOTE-VENDA')->first();
        $this->assertEquals(10, (float) $batch->fresh()->quantity_reserved);
        $this->assertEquals(1, StockReservation::where('sale_order_id', $orderId)->where('status', 'reserved')->count());

        // aprovado -> separacao
        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200);

        $this->confirmPickingForOrder($orderId);

        // separacao -> faturado (baixa)
        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'faturado');

        $batch->refresh();
        $this->assertEquals(0, (float) $batch->quantity_reserved);
        $this->assertEquals(20, (float) $batch->quantity_available);
        $this->assertEquals(10, (float) $batch->quantity_sold);
        $this->assertEquals(20, (float) $product->fresh()->qtd_stock);

        $this->assertDatabaseHas('stock_movements', [
            'type'           => 'saida',
            'reference_type' => 'sale_order',
            'reference_id'   => $orderId,
        ]);
    }

    #[Test]
    public function uc05_cancel_releases_reserved_stock(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 20,
            'batch_number' => 'LOTE-CANCEL',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 5,
                'unit_price' => 10,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");

        $batch = Batch::where('batch_number', 'LOTE-CANCEL')->first();
        $this->assertEquals(5, (float) $batch->fresh()->quantity_reserved);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/cancel")
            ->assertStatus(200);

        $this->assertEquals(0, (float) $batch->fresh()->quantity_reserved);
        $this->assertEquals(20, (float) $batch->fresh()->quantity_available);
    }
}
