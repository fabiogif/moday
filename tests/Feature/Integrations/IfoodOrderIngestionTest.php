<?php

namespace Tests\Feature\Integrations;

use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Integrations\Ifood\IfoodOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class IfoodOrderIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_it_persists_ifood_order_with_full_payload(): void
    {
        $tenant = Tenant::query()->create([
            'plan_id' => null,
            'uuid' => (string) Str::uuid(),
            'cnpj' => '12345678000100',
            'name' => 'Empresa Teste',
            'slug' => 'empresa-teste',
            'email' => 'tenant@example.com',
            'active' => 'Y',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'tenant_id' => $tenant->id,
            'sku' => 'SKU-123',
            'name' => 'Produto Teste',
            'price' => 15,
        ]);

        $payload = $this->makePayload();

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->app->instance(OrderRepositoryInterface::class, $orderRepository);

        $stubOrder = new Order();
        $stubOrder->setRawAttributes([
            'id' => 999,
            'tenant_id' => $tenant->id,
            'identify' => 'IFD12345',
            'status' => 'Recebido do iFood',
            'total' => 28,
        ], true);
        $stubOrder->exists = true;

        $orderRepository->shouldReceive('createNewOrder')
            ->once()
            ->andReturn($stubOrder);

        $orderRepository->shouldReceive('registerProductsOrder')
            ->once()
            ->with(999, Mockery::any());

        DB::table('orders')->insert([
            'id' => 999,
            'tenant_id' => $tenant->id,
            'identify' => 'IFD12345',
            'total' => 28,
            'subtotal' => null,
            'discount_amount' => null,
            'status' => 'Recebido do iFood',
            'is_delivery' => true,
            'use_client_address' => false,
            'coupon_snapshot' => null,
            'archived_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var IfoodOrderService $service */
        $service = $this->app->make(IfoodOrderService::class);

        $ifoodOrder = $service->ingestOrder($payload, $tenant->id);

        $order = $ifoodOrder->fresh(['items', 'order']);

        $this->assertSame('DELIVERY', $order->order_type);
        $this->assertSame('IMMEDIATE', $order->order_timing);
        $this->assertSame('IFOOD', $order->sales_channel);
        $this->assertSame('FOOD', $order->category);
        $this->assertSame('fc999', $order->display_id);
        $this->assertSame('merchant-001', $order->merchant_identifier);
        $this->assertSame('Loja Teste', $order->merchant_name);
        $this->assertSame('12345678900', $order->customer_document_number);
        $this->assertSame(5, $order->customer_orders_count);
        $this->assertSame('Gold', $order->customer_segmentation);
        $this->assertEquals(28.0, (float) $order->total_amount);
        $this->assertEquals(30.0, (float) $order->items_total);
        $this->assertEquals(1.0, (float) $order->delivery_fee);
        $this->assertEquals(5.0, (float) $order->benefits_total);
        $this->assertEquals(3.0, (float) $order->additional_fees_total);
        $this->assertFalse($order->is_test);
        $this->assertSame('Pago Online. NÃO LEVAR MÁQUINA', $order->extra_info);
        $this->assertNotNull($order->order);

        $this->assertCount(1, $order->items);
        $item = $order->items->first();

        $this->assertSame('SKU-123', $item->sku);
        $this->assertSame('unique-1', $item->unique_id);
        $this->assertSame('SKU-123', $item->external_code);
        $this->assertSame('Produto Combo', $item->name);
        $this->assertSame('UN', $item->unit);
        $this->assertEquals(2.0, (float) $item->quantity_value);
        $this->assertEquals(15.0, (float) $item->unit_price);
        $this->assertEquals(30.0, (float) $item->total_price);
        $this->assertEquals(2.0, (float) $item->options_price);
        $this->assertSame('Sem cebola', $item->observations);
        $this->assertNotEmpty($item->options);
        $this->assertNotEmpty($item->metadata);
    }

    private function makePayload(): array
    {
        $now = now();

        return [
            'id' => '32c15e00-9861-4548-b5f0-15580defc999',
            'displayId' => 'fc999',
            'orderType' => 'DELIVERY',
            'orderTiming' => 'IMMEDIATE',
            'salesChannel' => 'IFOOD',
            'category' => 'FOOD',
            'createdAt' => $now->toIso8601String(),
            'preparationStartDateTime' => $now->addMinutes(15)->toIso8601String(),
            'isTest' => false,
            'extraInfo' => 'Pago Online. NÃO LEVAR MÁQUINA',
            'merchant' => [
                'id' => 'merchant-001',
                'name' => 'Loja Teste',
            ],
            'customer' => [
                'id' => 'customer-xyz',
                'name' => 'Cliente Final',
                'documentNumber' => '12345678900',
                'ordersCountOnMerchant' => 5,
                'segmentation' => 'Gold',
                'phone' => [
                    'number' => '+5511999998888',
                ],
            ],
            'items' => [
                [
                    'index' => 0,
                    'id' => 'item-001',
                    'uniqueId' => 'unique-1',
                    'externalCode' => 'SKU-123',
                    'sku' => 'SKU-123',
                    'ean' => '1234567890123',
                    'name' => 'Produto Combo',
                    'quantity' => 2,
                    'unit' => 'UN',
                    'price' => [
                        'unit' => 1500,
                        'total' => 3000,
                    ],
                    'optionsPrice' => 200,
                    'customizationPrice' => 0,
                    'observations' => 'Sem cebola',
                    'options' => [
                        [
                            'id' => 'opt-1',
                            'name' => 'Queijo extra',
                            'price' => [
                                'amount' => 200,
                            ],
                        ],
                    ],
                    'scalePrices' => [
                        [
                            'from' => 1,
                            'to' => 10,
                            'price' => 1500,
                        ],
                    ],
                ],
            ],
            'benefits' => [
                [
                    'type' => 'DISCOUNT',
                    'value' => 500,
                ],
            ],
            'additionalFees' => [
                [
                    'type' => 'SERVICE_FEE',
                    'price' => 300,
                ],
            ],
            'payments' => [
                [
                    'method' => 'ONLINE',
                    'value' => 2800,
                ],
            ],
            'picking' => [
                'assigned' => true,
            ],
            'delivery' => [
                'deliveredBy' => 'IFOOD',
                'deliveryAddress' => [
                    'street' => 'Rua das Flores',
                    'number' => '123',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                    'postalCode' => '01000-000',
                ],
            ],
            'takeout' => [],
            'dineIn' => [],
            'schedule' => [
                'deliveryDateTime' => now()->addHour()->toIso8601String(),
            ],
            'additionalInfo' => [
                'notes' => 'Entregar rápido',
            ],
            'total' => [
                'orderAmount' => 2800,
                'subTotal' => 3000,
                'deliveryFee' => 100,
            ],
            'status' => [
                [
                    'state' => 'RECEIVED',
                    'createdAt' => now()->toIso8601String(),
                ],
            ],
        ];
    }
}

