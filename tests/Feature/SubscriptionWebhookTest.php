<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Plan   $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan   = Plan::factory()->create(['is_active' => true]);
        $this->tenant = Tenant::factory()->create([
            'plan_id'        => $this->plan->id,
            'account_status' => 'trial',
        ]);
    }

    private function fakeOrderApproved(string $externalRef, string $paymentStatus = 'processed'): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders/*' => Http::response([
                'id'                 => 'order_wh_1',
                'status'             => 'processed',
                'external_reference' => $externalRef,
                'transactions'       => [
                    'payments' => [[
                        'id'     => 'pay_wh_1',
                        'status' => $paymentStatus,
                    ]],
                ],
            ], 200),
        ]);
    }

    private function webhookPayload(string $orderId = 'order_wh_1'): array
    {
        return ['type' => 'order', 'data' => ['id' => $orderId]];
    }

    // ── Acesso público ────────────────────────────────────────────────────────

    #[Test]
    public function webhook_is_publicly_accessible_without_auth(): void
    {
        // Use an unknown topic so the controller returns early without hitting MP API
        $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'invoice',
            'data' => ['id' => 'some_id'],
        ])->assertStatus(200)
          ->assertJson(['ok' => true]);
    }

    // ── Ativação de assinatura ────────────────────────────────────────────────

    #[Test]
    public function webhook_activates_subscription_when_payment_approved(): void
    {
        $externalRef = "tenant_{$this->tenant->id}_plan_{$this->plan->id}_" . time();
        $this->fakeOrderApproved($externalRef);

        $this->postJson('/api/webhooks/mercadopago', $this->webhookPayload())
             ->assertStatus(200)
             ->assertJson(['ok' => true]);

        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
    }

    #[Test]
    public function webhook_sets_plan_id_on_tenant_after_activation(): void
    {
        $externalRef = "tenant_{$this->tenant->id}_plan_{$this->plan->id}_" . time();
        $this->fakeOrderApproved($externalRef);

        $this->postJson('/api/webhooks/mercadopago', $this->webhookPayload())
             ->assertStatus(200);

        $this->tenant->refresh();
        $this->assertEquals($this->plan->id, $this->tenant->plan_id);
    }

    // ── Idempotência ─────────────────────────────────────────────────────────

    #[Test]
    public function webhook_returns_already_active_when_same_plan_paid_again(): void
    {
        $this->tenant->update(['account_status' => 'active', 'plan_id' => $this->plan->id]);

        $externalRef = "tenant_{$this->tenant->id}_plan_{$this->plan->id}_" . time();
        $this->fakeOrderApproved($externalRef);

        $this->postJson('/api/webhooks/mercadopago', $this->webhookPayload())
             ->assertStatus(200)
             ->assertJson(['ok' => true, 'status' => 'already_active']);
    }

    #[Test]
    public function webhook_migrates_plan_when_active_tenant_pays_for_different_plan(): void
    {
        $newPlan = Plan::factory()->create(['is_active' => true]);
        $this->tenant->update(['account_status' => 'active', 'plan_id' => $this->plan->id]);

        $externalRef = "tenant_{$this->tenant->id}_plan_{$newPlan->id}_" . time();
        $this->fakeOrderApproved($externalRef);

        $this->postJson('/api/webhooks/mercadopago', $this->webhookPayload())
             ->assertStatus(200)
             ->assertJson(['ok' => true]);

        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
        $this->assertEquals($newPlan->id, $this->tenant->plan_id);
    }

    // ── Tópicos e IDs inválidos ───────────────────────────────────────────────

    #[Test]
    public function webhook_ignores_unknown_topic(): void
    {
        $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'chargeback',
            'data' => ['id' => 'some_id'],
        ])->assertStatus(200)
          ->assertJson(['ok' => true]);

        $this->tenant->refresh();
        $this->assertEquals('trial', $this->tenant->account_status);
    }

    #[Test]
    public function webhook_ignores_missing_id(): void
    {
        $this->postJson('/api/webhooks/mercadopago', ['type' => 'order'])
             ->assertStatus(200)
             ->assertJson(['ok' => true]);
    }

    // ── Erros na MP API ───────────────────────────────────────────────────────

    #[Test]
    public function webhook_returns_404_when_order_not_found_in_mp(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders/*' => Http::response([], 404),
        ]);

        $this->postJson('/api/webhooks/mercadopago', $this->webhookPayload('nonexistent'))
             ->assertStatus(404)
             ->assertJson(['error' => 'order not found']);
    }

    #[Test]
    public function webhook_returns_422_for_invalid_external_reference(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders/*' => Http::response([
                'id'                 => 'order_wh_3',
                'status'             => 'processed',
                'external_reference' => 'formato_invalido_sem_ids',
                'transactions'       => ['payments' => [['id' => 'p1', 'status' => 'processed']]],
            ], 200),
        ]);

        $this->postJson('/api/webhooks/mercadopago', $this->webhookPayload())
             ->assertStatus(422)
             ->assertJson(['error' => 'invalid reference']);

        $this->tenant->refresh();
        $this->assertEquals('trial', $this->tenant->account_status);
    }

    // ── Pagamento recusado ────────────────────────────────────────────────────

    #[Test]
    public function webhook_does_not_activate_for_rejected_payment(): void
    {
        $externalRef = "tenant_{$this->tenant->id}_plan_{$this->plan->id}_" . time();
        $this->fakeOrderApproved($externalRef, 'rejected');

        $this->postJson('/api/webhooks/mercadopago', $this->webhookPayload())
             ->assertStatus(200);

        $this->tenant->refresh();
        $this->assertEquals('trial', $this->tenant->account_status);
    }

    // ── Acesso via query params (formato alternativo do MP) ───────────────────

    #[Test]
    public function webhook_accepts_topic_via_query_param(): void
    {
        $externalRef = "tenant_{$this->tenant->id}_plan_{$this->plan->id}_" . time();
        $this->fakeOrderApproved($externalRef);

        $this->postJson('/api/webhooks/mercadopago?topic=order&id=order_wh_1', [])
             ->assertStatus(200);

        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
    }
}
