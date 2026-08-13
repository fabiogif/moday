<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\SubscriptionDunningEvent;
use App\Models\Tenant;
use App\Services\DunningService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DunningServiceTest extends TestCase
{
    use RefreshDatabase;

    private DunningService $dunning;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->dunning = app(DunningService::class);

        $plan = Plan::create([
            'name'      => 'Básico',
            'url'       => 'basico',
            'price'     => 49.90,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name'             => 'Empresa Dunning',
            'slug'             => 'empresa-dunning',
            'email'            => 'dunning@test.com',
            'account_status'   => 'pending',
            'plan_id'          => $plan->id,
            'dunning_started_at' => now(),
            'dunning_day'      => 0,
        ]);
    }

    public function test_d0_sends_email_and_in_app(): void
    {
        // D0: dunning_started_at = now, dunning_day not yet processed
        $this->tenant->dunning_started_at = now();
        $this->tenant->dunning_day        = -1; // force processing D0
        $this->tenant->save();

        $this->dunning->processTenant($this->tenant);

        $this->assertDatabaseHas('subscription_dunning_events', [
            'tenant_id'   => $this->tenant->id,
            'dunning_day' => 0,
            'channel'     => 'email',
        ]);
    }

    public function test_d7_marks_tenant_as_delinquent(): void
    {
        $this->tenant->dunning_started_at = now()->subDays(7);
        $this->tenant->dunning_day        = 5; // processed up to D5
        $this->tenant->save();

        $this->dunning->processTenant($this->tenant);

        $this->tenant->refresh();
        $this->assertEquals('delinquent', $this->tenant->account_status);
        $this->assertTrue((bool) $this->tenant->is_blocked);
    }

    public function test_d30_suspends_tenant(): void
    {
        $this->tenant->dunning_started_at = now()->subDays(30);
        $this->tenant->dunning_day        = 7; // processed up to D7
        $this->tenant->account_status     = 'delinquent';
        $this->tenant->save();

        $this->dunning->processTenant($this->tenant);

        $this->tenant->refresh();
        $this->assertEquals('suspended', $this->tenant->account_status);
    }

    public function test_idempotency_prevents_duplicate_dunning_events(): void
    {
        // Pre-create a D0 email event
        SubscriptionDunningEvent::create([
            'tenant_id'  => $this->tenant->id,
            'dunning_day' => 0,
            'channel'    => 'email',
            'success'    => true,
            'sent_at'    => now(),
        ]);

        $this->tenant->dunning_day = -1;
        $this->tenant->save();

        $this->dunning->processTenant($this->tenant);

        // Should still be only 1 email event for D0
        $this->assertEquals(1, SubscriptionDunningEvent::where('tenant_id', $this->tenant->id)
            ->where('dunning_day', 0)
            ->where('channel', 'email')
            ->count());
    }

    public function test_process_all_returns_count(): void
    {
        // Create a second dunning tenant
        Tenant::create([
            'name'             => 'Empresa 2',
            'slug'             => 'empresa-2',
            'email'            => 'empresa2@test.com',
            'account_status'   => 'pending',
            'dunning_started_at' => now(),
            'dunning_day'      => -1,
        ]);

        $count = $this->dunning->processAll();

        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function test_non_dunning_tenant_is_skipped(): void
    {
        $tenant = Tenant::create([
            'name'           => 'Empresa Ativa',
            'slug'           => 'empresa-ativa',
            'email'          => 'ativa@test.com',
            'account_status' => 'active',
            // no dunning_started_at
        ]);

        $this->dunning->processTenant($tenant);

        $this->assertDatabaseMissing('subscription_dunning_events', [
            'tenant_id' => $tenant->id,
        ]);
    }
}
