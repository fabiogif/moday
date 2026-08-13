<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User   $user;
    private Tenant $tenant;
    private Plan   $plan;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan   = Plan::factory()->create(['price' => 99.90, 'is_active' => true]);
        $this->tenant = Tenant::factory()->create([
            'plan_id'        => $this->plan->id,
            'account_status' => 'trial',
        ]);
        $this->user  = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    private function fakeMpApproved(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders' => Http::response([
                'id'     => 'order_approved',
                'status' => 'processed',
                'transactions' => [
                    'payments' => [[
                        'id'            => 'pay_approved',
                        'status'        => 'processed',
                        'status_detail' => 'accredited',
                    ]],
                ],
            ], 201),
        ]);
    }

    private function fakeMpRejected(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/orders' => Http::response([
                'id'     => 'order_rejected',
                'status' => 'processed',
                'transactions' => [
                    'payments' => [[
                        'id'            => 'pay_rejected',
                        'status'        => 'rejected',
                        'status_detail' => 'cc_rejected_bad_filled_card_number',
                    ]],
                ],
            ], 201),
        ]);
    }

    private function validPayload(): array
    {
        return [
            'plan_id'            => $this->plan->id,
            'token'              => 'card_token_test',
            'payment_method_id'  => 'visa',
            'payment_type_id'    => 'credit_card',
            'transaction_amount' => 99.90,
            'installments'       => 1,
            'payer_email'        => 'user@example.com',
        ];
    }

    // ── GET /api/subscription/plans ──────────────────────────────────────────

    #[Test]
    public function it_lists_active_plans(): void
    {
        $response = $this->getJson('/api/subscription/plans', $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data']);
    }

    // ── GET /api/subscription/trial-status ───────────────────────────────────

    #[Test]
    public function it_returns_trial_status_for_authenticated_user(): void
    {
        $response = $this->getJson('/api/subscription/trial-status', $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data' => ['is_trial', 'account_status', 'days_remaining']]);
    }

    #[Test]
    public function it_requires_authentication_for_trial_status(): void
    {
        $this->getJson('/api/subscription/trial-status')
             ->assertStatus(401);
    }

    // ── POST /api/subscription/payment ───────────────────────────────────────

    #[Test]
    public function it_processes_payment_and_activates_subscription(): void
    {
        $this->fakeMpApproved();

        $response = $this->postJson('/api/subscription/payment', $this->validPayload(), $this->authHeaders());

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
    }

    #[Test]
    public function it_returns_422_when_payment_is_rejected(): void
    {
        $this->fakeMpRejected();

        $response = $this->postJson('/api/subscription/payment', $this->validPayload(), $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);

        $this->tenant->refresh();
        $this->assertNotEquals('active', $this->tenant->account_status);
    }

    #[Test]
    public function it_validates_required_payment_fields(): void
    {
        $response = $this->postJson('/api/subscription/payment', [], $this->authHeaders());

        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_payment_type_id_enum(): void
    {
        $payload = array_merge($this->validPayload(), ['payment_type_id' => 'pix']);

        $response = $this->postJson('/api/subscription/payment', $payload, $this->authHeaders());

        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_installments_range(): void
    {
        $payload = array_merge($this->validPayload(), ['installments' => 0]);
        $this->postJson('/api/subscription/payment', $payload, $this->authHeaders())
             ->assertStatus(422);

        $payload['installments'] = 25;
        $this->postJson('/api/subscription/payment', $payload, $this->authHeaders())
             ->assertStatus(422);
    }

    #[Test]
    public function it_returns_422_for_nonexistent_plan(): void
    {
        // The exists:plans,id validation rule catches this before the controller
        $payload = array_merge($this->validPayload(), ['plan_id' => 9999]);

        $response = $this->postJson('/api/subscription/payment', $payload, $this->authHeaders());

        $response->assertStatus(422)
                 ->assertJsonPath('errors.plan_id.0', 'O plano selecionado não existe.');
    }

    #[Test]
    public function it_requires_authentication_for_payment(): void
    {
        $this->postJson('/api/subscription/payment', $this->validPayload())
             ->assertStatus(401);
    }

    #[Test]
    public function it_creates_billing_record_after_successful_payment(): void
    {
        $this->fakeMpApproved();

        $this->postJson('/api/subscription/payment', $this->validPayload(), $this->authHeaders())
             ->assertStatus(200);

        $this->assertDatabaseHas('tenant_billing', [
            'tenant_id' => $this->tenant->id,
            'status'    => 'paid',
        ]);
    }

    // ── POST /api/subscription/activate (plano gratuito) ──────────────────────

    #[Test]
    public function expired_tenant_can_activate_free_plan_without_payment(): void
    {
        $freePlan = Plan::factory()->create([
            'name'      => 'Grátis',
            'price'     => 0,
            'is_active' => true,
        ]);

        $this->tenant->update([
            'account_status' => 'expired',
            'plan_id'        => null,
        ]);

        $response = $this->postJson('/api/subscription/activate', [
            'plan_id'         => $freePlan->id,
            'payment_method'  => 'free',
        ], $this->authHeaders());

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
        $this->assertEquals($freePlan->id, $this->tenant->plan_id);
        $this->assertEquals('Grátis', $this->tenant->subscription_plan);
    }

    #[Test]
    public function activate_rejects_paid_plan_without_payment(): void
    {
        $response = $this->postJson('/api/subscription/activate', [
            'plan_id'        => $this->plan->id,
            'payment_method' => 'free',
        ], $this->authHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'pagamento',
            mb_strtolower($response->json('message') ?? '')
        );
    }
}
