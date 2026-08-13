<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\SubscriptionEvent;
use App\Models\Tenant;
use App\Models\TrialFingerprint;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Services\SubscriptionAuditService;
use App\Services\SubscriptionDomainService;
use App\Services\TrialFraudService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SubscriptionDomainServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionDomainService $domain;
    private MercadoPagoService $mpMock;
    private Plan $planBasico;
    private Plan $planPro;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mpMock = Mockery::mock(MercadoPagoService::class);
        $this->app->instance(MercadoPagoService::class, $this->mpMock);

        $this->domain = app(SubscriptionDomainService::class);

        $this->planBasico = Plan::create([
            'name'        => 'Básico',
            'url'         => 'basico',
            'price'       => 49.90,
            'description' => '',
            'is_active'   => true,
            'max_users'   => 5,
            'max_products'=> 100,
        ]);

        $this->planPro = Plan::create([
            'name'        => 'Pro',
            'url'         => 'pro',
            'price'       => 99.90,
            'description' => '',
            'is_active'   => true,
            'max_users'   => 20,
            'max_products'=> 500,
        ]);

        $this->tenant = Tenant::create([
            'name'           => 'Empresa Teste',
            'slug'           => 'empresa-teste',
            'email'          => 'test@empresa.com',
            'cnpj'           => '12345678000199',
            'account_status' => 'active',
            'plan_id'        => $this->planBasico->id,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── Trial ─────────────────────────────────────────────────────────────────

    public function test_start_trial_sets_status_and_registers_fingerprint(): void
    {
        $tenant = Tenant::create([
            'name'           => 'Nova Empresa',
            'slug'           => 'nova-empresa',
            'email'          => 'new@empresa.com',
            'cnpj'           => '98765432000111',
            'account_status' => 'expired',
        ]);

        $this->domain->startTrial($tenant);

        $tenant->refresh();
        $this->assertEquals('trial', $tenant->account_status);
        $this->assertNotNull($tenant->trial_started_at);
        $this->assertTrue(TrialFingerprint::hasUsedTrial('98765432000111'));
    }

    public function test_start_trial_blocked_when_document_already_used(): void
    {
        TrialFingerprint::create([
            'document'  => '12345678000199',
            'doc_type'  => 'cnpj',
            'tenant_id' => $this->tenant->id,
        ]);

        $anotherTenant = Tenant::create([
            'name'           => 'Outra Empresa',
            'slug'           => 'outra-empresa',
            'email'          => 'outra@empresa.com',
            'cnpj'           => '12345678000199', // same CNPJ
            'account_status' => 'expired',
        ]);

        $this->expectException(\DomainException::class);
        $this->domain->startTrial($anotherTenant);
    }

    // ── Upgrade ───────────────────────────────────────────────────────────────

    public function test_upgrade_updates_plan_and_mp_amount(): void
    {
        $this->tenant->mp_subscription_id = 'pre_test_123';
        $this->tenant->save();

        $this->mpMock
            ->shouldReceive('updateSubscriptionAmount')
            ->once()
            ->with('pre_test_123', 99.90, Mockery::any())
            ->andReturn(['success' => true, 'data' => []]);

        $this->domain->upgrade($this->tenant, $this->planPro);

        $this->tenant->refresh();
        $this->assertEquals($this->planPro->id, $this->tenant->plan_id);
        $this->assertEquals(1, SubscriptionEvent::where('tenant_id', $this->tenant->id)
            ->where('event_type', 'upgrade')->count());
    }

    public function test_upgrade_fails_when_target_plan_is_lower(): void
    {
        $this->tenant->plan_id = $this->planPro->id;
        $this->tenant->save();

        $this->expectException(\DomainException::class);
        $this->domain->upgrade($this->tenant, $this->planBasico);
    }

    // ── Downgrade ─────────────────────────────────────────────────────────────

    public function test_schedule_downgrade_sets_fields(): void
    {
        $this->tenant->plan_id           = $this->planPro->id;
        $this->tenant->next_billing_date = now()->addMonth()->toDateString();
        $this->tenant->mp_subscription_id = 'pre_test_123';
        $this->tenant->save();

        $this->domain->scheduleDowngrade($this->tenant, $this->planBasico);

        $this->tenant->refresh();
        $this->assertEquals($this->planBasico->id, $this->tenant->scheduled_downgrade_plan_id);
        $this->assertNotNull($this->tenant->scheduled_downgrade_at);
    }

    public function test_schedule_downgrade_fails_when_target_is_higher(): void
    {
        $this->expectException(\DomainException::class);
        $this->domain->scheduleDowngrade($this->tenant, $this->planPro);
    }

    public function test_apply_scheduled_downgrade_succeeds(): void
    {
        $this->tenant->plan_id                      = $this->planPro->id;
        $this->tenant->scheduled_downgrade_plan_id  = $this->planBasico->id;
        $this->tenant->scheduled_downgrade_at       = now()->subDay()->toDateString();
        $this->tenant->scheduled_downgrade_attempts = 0;
        $this->tenant->mp_subscription_id           = 'pre_test_123';
        $this->tenant->save();

        $this->mpMock
            ->shouldReceive('updateSubscriptionAmount')
            ->once()
            ->andReturn(['success' => true, 'data' => []]);

        $result = $this->domain->applyScheduledDowngrade($this->tenant);

        $this->assertTrue($result);
        $this->tenant->refresh();
        $this->assertEquals($this->planBasico->id, $this->tenant->plan_id);
        $this->assertNull($this->tenant->scheduled_downgrade_plan_id);
    }

    public function test_apply_downgrade_increments_attempts_on_limit_failure(): void
    {
        // Give the tenant too many users for the basico plan
        User::create([
            'name'           => 'User 1',
            'email'          => 'u1@test.com',
            'password'       => bcrypt('password'),
            'tenant_id'      => $this->tenant->id,
        ]);
        for ($i = 2; $i <= 6; $i++) {
            User::create([
                'name'      => "User {$i}",
                'email'     => "u{$i}@test.com",
                'password'  => bcrypt('password'),
                'tenant_id' => $this->tenant->id,
            ]);
        }

        $this->tenant->plan_id                      = $this->planPro->id;
        $this->tenant->scheduled_downgrade_plan_id  = $this->planBasico->id; // max_users=5, tenant has 6
        $this->tenant->scheduled_downgrade_at       = now()->subDay()->toDateString();
        $this->tenant->scheduled_downgrade_attempts = 0;
        $this->tenant->save();

        $result = $this->domain->applyScheduledDowngrade($this->tenant);

        $this->assertFalse($result);
        $this->tenant->refresh();
        $this->assertEquals(1, $this->tenant->scheduled_downgrade_attempts);
        $this->assertNotNull($this->tenant->scheduled_downgrade_plan_id);
    }

    // ── Cancellation ─────────────────────────────────────────────────────────

    public function test_request_cancellation_sets_flag_and_cancels_mp(): void
    {
        $this->tenant->mp_subscription_id = 'pre_test_123';
        $this->tenant->account_status     = 'active';
        $this->tenant->current_period_end = now()->addDays(10);
        $this->tenant->save();

        $this->mpMock
            ->shouldReceive('cancelSubscription')
            ->once()
            ->andReturn(['success' => true, 'data' => []]);

        $this->domain->requestCancellation($this->tenant, 'too_expensive', null);

        $this->tenant->refresh();
        $this->assertNotNull($this->tenant->cancellation_requested_at);
        $this->assertEquals('active', $this->tenant->account_status); // still active until period end

        $event = SubscriptionEvent::where('tenant_id', $this->tenant->id)
            ->where('event_type', SubscriptionEvent::CANCELLATION_REQUESTED)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals('too_expensive', $event->metadata['reason'] ?? null);
    }

    public function test_request_cancellation_throws_when_already_cancelled(): void
    {
        $this->tenant->account_status = 'cancelled';
        $this->tenant->save();

        $this->mpMock->shouldNotReceive('cancelSubscription');

        $this->expectException(\DomainException::class);
        $this->domain->requestCancellation($this->tenant);
    }

    public function test_finalize_cancellation_sets_cancelled_status(): void
    {
        $this->tenant->cancellation_requested_at = now()->subDay();
        $this->tenant->account_status            = 'active';
        $this->tenant->save();

        $this->domain->finalizeCancellation($this->tenant);

        $this->tenant->refresh();
        $this->assertEquals('cancelled', $this->tenant->account_status);
        $this->assertNotNull($this->tenant->cancelled_at);
    }

    // ── Webhook handling ─────────────────────────────────────────────────────

    public function test_handle_preapproval_webhook_activates_on_authorized(): void
    {
        $this->tenant->account_status     = 'pending';
        $this->tenant->dunning_started_at = now()->subDays(2);
        $this->tenant->save();

        $this->mpMock
            ->shouldReceive('mapMpStatusToInternal')
            ->with('authorized')
            ->andReturn('active');

        $this->mpMock
            ->shouldReceive('extractNextBillingDate')
            ->andReturn(Carbon::now()->addMonth());

        $this->domain->handlePreapprovalWebhook($this->tenant, ['status' => 'authorized']);

        $this->tenant->refresh();
        $this->assertEquals('active', $this->tenant->account_status);
        $this->assertNull($this->tenant->dunning_started_at);
    }
}
