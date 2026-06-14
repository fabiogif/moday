<?php

namespace Tests\Feature\Integrations;

use App\Services\Integrations\Ifood\IfoodOrderService;
use Mockery;
use Tests\TestCase;

class IfoodWebhookTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    public function test_webhook_processes_order_payload(): void
    {
        config(['services.ifood.webhook_secret' => 'secret-key']);

        $service = Mockery::mock(IfoodOrderService::class);
        $this->app->instance(IfoodOrderService::class, $service);

        $payload = [
            'order' => [
                'id' => '123',
                'displayId' => '123',
                'createdAt' => now()->toIso8601String(),
                'items' => [
                    [
                        'id' => 'item-1',
                        'name' => 'Produto Teste',
                        'quantity' => 1,
                        'price' => [
                            'unit' => 1500,
                            'total' => 1500,
                        ],
                    ],
                ],
                'customer' => [
                    'name' => 'Cliente Teste',
                    'phone' => '+5511999999999',
                ],
                'delivery' => [
                    'deliveryAddress' => [
                        'street' => 'Rua Exemplo',
                        'number' => '100',
                        'city' => 'São Paulo',
                        'state' => 'SP',
                        'postalCode' => '01000-000',
                    ],
                ],
                'total' => [
                    'orderAmount' => 1500,
                ],
            ],
        ];

        $service->shouldReceive('ingestOrder')
            ->once()
            ->with($payload['order'], 1);

        $signature = base64_encode(hash_hmac('sha256', json_encode($payload), 'secret-key', true));

        $this->postJson('/api/integrations/ifood/webhook?tenant_id=1', $payload, [
            'X-Signature' => $signature,
        ])->assertAccepted();
    }
}

