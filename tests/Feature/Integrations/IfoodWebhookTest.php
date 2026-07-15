<?php

namespace Tests\Feature\Integrations;

use App\Jobs\ProcessIfoodEventJob;
use App\Models\Integrations\Ifood\IfoodEvent;
use App\Models\Tenant;
use App\Services\Integrations\Ifood\IfoodEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class IfoodWebhookTest extends TestCase
{
    use RefreshDatabase;

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
        Queue::fake();

        $tenant = Tenant::factory()->create();

        $service = Mockery::mock(IfoodEventService::class);
        $this->app->instance(IfoodEventService::class, $service);

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

        $event = new IfoodEvent();
        $event->forceFill([
            'id' => 42,
            'tenant_id' => $tenant->id,
            'event_id' => '123',
            'status' => 'pending',
        ]);
        $event->exists = true;

        $service->shouldReceive('recordEvent')
            ->once()
            ->withArgs(function ($tenantId, $eventPayload) use ($tenant, $payload) {
                return (int) $tenantId === (int) $tenant->id
                    && is_array($eventPayload)
                    && isset($eventPayload['order']['id'])
                    && $eventPayload['order']['id'] === $payload['order']['id'];
            })
            ->andReturn($event);

        $rawBody = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $rawBody, 'secret-key', true));

        $this->call(
            'POST',
            "/api/integrations/ifood/webhook?tenant_id={$tenant->id}",
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X-SIGNATURE' => $signature,
            ],
            $rawBody
        )->assertAccepted();

        Queue::assertPushed(ProcessIfoodEventJob::class);
    }
}
