<?php

namespace Tests\Feature\Distribtec\Integration\Phase3;

use App\Models\SaleOrder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Integration\Traits\DistribtecTestHelpers;
use Tests\TestCase;

#[Group('integration')]
class Uc13PickingAndShipmentTest extends TestCase
{
    use DistribtecTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDistribtecTenant();
    }

    #[Test]
    public function uc13_picking_list_and_confirm_before_billing(): void
    {
        $product   = $this->createProduct(['qtd_stock' => 0]);
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 20,
            'batch_number' => 'PICK-001',
        ])->assertStatus(201);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 8,
                'unit_price' => 15,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/sale-orders/{$orderId}/picking")
            ->assertStatus(200)
            ->assertJsonPath('data.0.quantity', 8)
            ->assertJsonPath('data.0.is_complete', false);

        $this->confirmPickingForOrder($orderId);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/sale-orders/{$orderId}/picking")
            ->assertStatus(200)
            ->assertJsonPath('data.0.is_complete', true);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'faturado');
    }

    #[Test]
    public function uc13_cannot_bill_without_picking(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 10,
            'batch_number' => 'PICK-002',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 3,
                'unit_price' => 10,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");
        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/sale-orders/{$orderId}/advance-status")
            ->assertStatus(422);
    }

    #[Test]
    public function uc13_shipment_dispatch_and_delivery(): void
    {
        $product   = $this->createProduct();
        $warehouse = $this->createWarehouse();

        $this->withHeaders($this->authHeaders())->postJson('/api/stock-movements', [
            'type'         => 'entrada',
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => 15,
            'batch_number' => 'SHIP-001',
        ]);

        $orderId = $this->withHeaders($this->authHeaders())->postJson('/api/sale-orders', [
            'status' => 'orcamento',
            'items'  => [[
                'product_id' => $product->id,
                'quantity'   => 5,
                'unit_price' => 20,
            ]],
        ])->json('data.id');

        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");
        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");
        $this->confirmPickingForOrder($orderId);
        $this->withHeaders($this->authHeaders())->postJson("/api/sale-orders/{$orderId}/advance-status");

        $shipmentId = $this->withHeaders($this->authHeaders())->postJson('/api/shipments', [
            'route_name'     => 'Rota Centro',
            'driver_name'    => 'João Motorista',
            'vehicle_plate'  => 'ABC1D23',
            'sale_order_ids' => [$orderId],
        ])->assertStatus(201)->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/shipments/{$shipmentId}/dispatch")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'dispatched');

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/shipments/{$shipmentId}/deliver", ['pod_reference' => 'POD-123'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered');

        $this->assertEquals('entregue', SaleOrder::find($orderId)->status);
    }
}
