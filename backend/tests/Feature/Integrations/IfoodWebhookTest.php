<?php

namespace Tests\Feature\Integrations;

use App\Jobs\ProcessIfoodEventJob;
use App\Models\Integrations\Ifood\IfoodApiToken;
use App\Models\Integrations\Ifood\IfoodEvent;
use App\Models\Tenant;
use App\Services\Integrations\Ifood\IfoodEventService;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class IfoodWebhookTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    public function test_webhook_processes_order_payload(): void
    {
        config(['services.ifood.webhook_secret' => 'secret-key']);
        Queue::fake();

        // O webhook resolve o tenant a partir de qual tenant tem integração
        // iFood ativa (IfoodApiToken.is_active), não mais confiando no
        // tenant_id enviado no próprio request.
        $tenant = Tenant::factory()->create(['id' => 1]);
        IfoodApiToken::create([
            'tenant_id' => $tenant->id,
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_type' => 'Bearer',
            'expires_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $event = new IfoodEvent();
        $event->forceFill([
            'id' => 42,
            'tenant_id' => 1,
            'status' => 'pending',
        ]);

        $eventService = Mockery::mock(IfoodEventService::class);
        $this->app->instance(IfoodEventService::class, $eventService);

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

        $eventService->shouldReceive('recordEvent')
            ->once()
            ->withArgs(function (int $tenantId, array $event) {
                return $tenantId === 1 && isset($event['order']['id']) && $event['order']['id'] === '123';
            })
            ->andReturn($event);

        $rawBody = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $rawBody, 'secret-key', true));

        $this->call(
            'POST',
            '/api/integrations/ifood/webhook?tenant_id=1',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_SIGNATURE' => $signature,
            ],
            $rawBody
        )->assertAccepted();

        Queue::assertPushed(ProcessIfoodEventJob::class);
    }
}
