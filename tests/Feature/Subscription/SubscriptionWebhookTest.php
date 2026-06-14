<?php

namespace Tests\Feature\Subscription;

use App\Models\MpWebhookEvent;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable MP signature verification in tests
        config(['services.mercadopago.billing_mode' => 'legacy']);
        config(['services.mercadopago.webhook_secret' => '']);

        // Register the middleware alias so the route doesn't throw BindingResolutionException
        app('router')->aliasMiddleware('mp.signature', \App\Http\Middleware\VerifyMercadoPagoSignature::class);

        $this->plan = Plan::create([
            'name'      => 'Pro',
            'url'       => 'pro',
            'price'     => 99.90,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name'                 => 'Empresa Webhook',
            'slug'                 => 'empresa-webhook',
            'email'                => 'webhook@test.com',
            'account_status'       => 'active',
            'plan_id'              => $this->plan->id,
            'mp_subscription_id'   => 'preapproval_test_999',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_preapproval_webhook_idempotency(): void
    {
        // Pre-create the event to simulate duplicate
        MpWebhookEvent::create([
            'mp_event_id'  => 'preapproval:preapproval_test_999',
            'topic'        => 'preapproval',
            'payload'      => [],
            'status'       => 'processed',
            'processed_at' => now(),
        ]);

        $response = $this->postJson('/api/webhooks/mercadopago/preapproval', [
            'type'    => 'preapproval',
            'data'    => ['id' => 'preapproval_test_999'],
        ], [
            'x-signature'  => 'ts=1,v1=fake',
            'x-request-id' => 'req-001',
        ]);

        $response->assertOk()
            ->assertJson(['ok' => true, 'status' => 'duplicate']);
    }

    public function test_legacy_webhook_activates_subscription(): void
    {
        $mpMock = Mockery::mock(MercadoPagoService::class);
        $this->app->instance(MercadoPagoService::class, $mpMock);

        $mpMock->shouldReceive('getOrder')
            ->once()
            ->with('order_123')
            ->andReturn([
                'success' => true,
                'data' => [
                    'id'                 => 'order_123',
                    'status'             => 'processed',
                    'external_reference' => "tenant_{$this->tenant->id}_plan_{$this->plan->id}_1234567890",
                    'transactions'       => [
                        'payments' => [['status' => 'processed']],
                    ],
                ],
            ]);

        $mpMock->shouldReceive('mapMpStatusToInternal')->andReturn(null);
        $mpMock->shouldReceive('extractNextBillingDate')->andReturn(null);

        // Set tenant to trial so it gets activated
        $this->tenant->account_status = 'trial';
        $this->tenant->save();

        // Send topic and id as query params
        $response = $this->postJson('/api/webhooks/mercadopago?topic=payment&id=order_123');

        $response->assertOk();
        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
    }

    public function test_webhook_returns_ok_for_unknown_topic(): void
    {
        $response = $this->postJson('/api/webhooks/mercadopago/preapproval', [
            'type' => 'unknown_event',
            'data' => ['id' => 'some_id'],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
    }
}
