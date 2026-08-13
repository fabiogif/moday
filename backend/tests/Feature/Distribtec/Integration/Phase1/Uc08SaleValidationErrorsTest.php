<?php

namespace Tests\Feature\Distribtec\Integration\Phase1;

use App\Models\Batch;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc08SaleValidationErrorsTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc08_rejects_sale_when_insufficient_stock(): void
    {
        $product   = $this->createProduct(['qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 5,
            'batch_number' => 'LOTE-POUCO',
        ]);

        $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'aprovado',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 100,
                'unit_price' => 10,
            ]],
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function uc08_rejects_advance_when_insufficient_stock(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 2,
            'batch_number' => 'LOTE-MIN',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 10,
                'unit_price' => 10,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(422);
    }

    #[Test]
    public function uc08_rejects_expired_batch_allocation(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        Batch::factory()->expired()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $product->id,
            'warehouse_id'       => $warehouse->id,
            'batch_number'       => 'LOTE-VENCIDO',
            'quantity_received'  => 100,
            'quantity_available' => 100,
            'status'             => 'available',
        ]);

        $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'aprovado',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 10,
                'unit_price' => 10,
            ]],
        ])->assertStatus(422);
    }
}
