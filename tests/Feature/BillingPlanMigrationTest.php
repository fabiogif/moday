<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Testa migração de plano via pagamento:
 * - Assinante ativo trocando de plano
 * - Trial expirado assinando pela primeira vez
 * - Rejeição não altera o plano atual
 */
class BillingPlanMigrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Plan $planA;
    private Plan $planB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planA = Plan::factory()->create([
            'name'      => 'Básico',
            'price'     => '49.90',
            'is_active' => true,
        ]);

        $this->planB = Plan::factory()->create([
            'name'      => 'Premium',
            'price'     => '99.90',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::factory()->create([
            'account_status' => 'active',
            'plan_id'        => $this->planA->id,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . JWTAuth::fromUser($this->user)];
    }

    private function fakeMpApproved(string $orderId = 'ORDER_MIGRATE_001'): void
    {
        Http::fake([
            'https://api.mercadopago.com/v1/orders' => Http::response([
                'id'     => $orderId,
                'status' => 'processed',
                'transactions' => [
                    'payments' => [[
                        'id'     => 'PAY_' . $orderId,
                        'status' => 'processed',
                    ]],
                ],
            ], 201),
        ]);
    }

    private function fakeMpRejected(): void
    {
        Http::fake([
            'https://api.mercadopago.com/v1/orders' => Http::response([
                'id'     => 'ORDER_REJECTED',
                'status' => 'rejected',
                'transactions' => [
                    'payments' => [[
                        'id'            => 'PAY_REJECTED',
                        'status'        => 'rejected',
                        'status_detail' => 'cc_rejected_insufficient_amount',
                    ]],
                ],
            ], 201),
        ]);
    }

    private function paymentPayload(int $planId, float $amount = 99.90): array
    {
        return [
            'plan_id'            => $planId,
            'token'              => 'test-card-token-migrate',
            'payment_method_id'  => 'visa',
            'payment_type_id'    => 'credit_card',
            'transaction_amount' => $amount,
            'installments'       => 1,
            'payer_email'        => 'user@migration.test',
        ];
    }

    // ── Migração de plano por assinante ativo ─────────────────────────────────

    #[Test]
    public function active_subscriber_can_pay_to_migrate_plan(): void
    {
        $this->fakeMpApproved();

        $response = $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id),
            $this->authHeaders()
        );

        $response->assertStatus(200);
    }

    #[Test]
    public function migration_updates_plan_id_on_tenant(): void
    {
        $this->fakeMpApproved();

        $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id),
            $this->authHeaders()
        );

        $this->assertDatabaseHas('tenants', [
            'id'      => $this->tenant->id,
            'plan_id' => $this->planB->id,
        ]);
    }

    #[Test]
    public function migration_keeps_account_status_active(): void
    {
        $this->fakeMpApproved();

        $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id),
            $this->authHeaders()
        );

        $this->assertDatabaseHas('tenants', [
            'id'             => $this->tenant->id,
            'account_status' => 'active',
        ]);
    }

    #[Test]
    public function migration_response_contains_new_plan_name(): void
    {
        $this->fakeMpApproved();

        $response = $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id),
            $this->authHeaders()
        );

        $response->assertJsonPath('data.plan.name', 'Premium');
    }

    // ── Rejeição não altera o plano ───────────────────────────────────────────

    #[Test]
    public function rejected_payment_returns_422(): void
    {
        $this->fakeMpRejected();

        $response = $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id),
            $this->authHeaders()
        );

        $response->assertStatus(422);
    }

    #[Test]
    public function rejected_payment_does_not_change_plan(): void
    {
        $this->fakeMpRejected();

        $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id),
            $this->authHeaders()
        );

        $this->assertDatabaseHas('tenants', [
            'id'      => $this->tenant->id,
            'plan_id' => $this->planA->id,
        ]);
    }

    #[Test]
    public function rejected_payment_does_not_change_account_status(): void
    {
        $this->fakeMpRejected();

        $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id),
            $this->authHeaders()
        );

        $this->assertDatabaseHas('tenants', [
            'id'             => $this->tenant->id,
            'account_status' => 'active',
        ]);
    }

    // ── Trial expirado assinando ──────────────────────────────────────────────

    #[Test]
    public function expired_trial_user_can_subscribe_via_payment(): void
    {
        $this->tenant->update(['account_status' => 'expired', 'plan_id' => null]);
        $this->fakeMpApproved('ORDER_FIRST_SUB');

        $response = $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planA->id, 49.90),
            $this->authHeaders()
        );

        $response->assertStatus(200);
    }

    #[Test]
    public function expired_trial_subscription_becomes_active_after_payment(): void
    {
        $this->tenant->update(['account_status' => 'expired', 'plan_id' => null]);
        $this->fakeMpApproved('ORDER_FIRST_SUB');

        $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planA->id, 49.90),
            $this->authHeaders()
        );

        $this->assertDatabaseHas('tenants', [
            'id'             => $this->tenant->id,
            'account_status' => 'active',
            'plan_id'        => $this->planA->id,
        ]);
    }

    // ── Billing record ────────────────────────────────────────────────────────

    #[Test]
    public function migration_creates_billing_record_with_new_plan_price(): void
    {
        $this->fakeMpApproved();

        $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id),
            $this->authHeaders()
        );

        $this->assertDatabaseHas('tenant_billing', [
            'tenant_id' => $this->tenant->id,
            'amount'    => '99.90',
        ]);
    }

    // ── Autenticação ──────────────────────────────────────────────────────────

    #[Test]
    public function payment_requires_authentication(): void
    {
        $response = $this->postJson(
            '/api/subscription/payment',
            $this->paymentPayload($this->planB->id)
        );

        $response->assertStatus(401);
    }
}
