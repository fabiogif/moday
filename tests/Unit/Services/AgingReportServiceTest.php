<?php

namespace Tests\Unit\Services;

use App\Models\AccountReceivable;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AgingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgingReportService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AgingReportService();
        $plan          = Plan::factory()->create();
        $this->tenant  = Tenant::factory()->create(['plan_id' => $plan->id]);
    }

    private function createOverdueAR(int $daysAgo, float $amount, float $received = 0, ?int $clientId = null): AccountReceivable
    {
        return AccountReceivable::create([
            'tenant_id'       => $this->tenant->id,
            'client_id'       => $clientId,
            'issue_date'      => Carbon::today()->subDays($daysAgo),
            'due_date'        => Carbon::today()->subDays($daysAgo),
            'amount'          => $amount,
            'amount_received' => $received,
            'status'          => 'vencido',
            'description'     => "Test AR {$daysAgo}d",
        ]);
    }

    #[Test]
    public function generate_classifies_overdue_into_correct_buckets(): void
    {
        $this->createOverdueAR(15, 100);  // 1-30 dias
        $this->createOverdueAR(45, 200);  // 31-60 dias
        $this->createOverdueAR(75, 300);  // 61-90 dias
        $this->createOverdueAR(100, 400); // +90 dias

        $result = $this->service->generate($this->tenant->id);

        $buckets = collect($result['buckets'])->keyBy('label');

        $this->assertEquals(1, $buckets['1-30 dias']['count']);
        $this->assertEquals(100.0, $buckets['1-30 dias']['total_amount']);

        $this->assertEquals(1, $buckets['31-60 dias']['count']);
        $this->assertEquals(200.0, $buckets['31-60 dias']['total_amount']);

        $this->assertEquals(1, $buckets['61-90 dias']['count']);
        $this->assertEquals(1, $buckets['+90 dias']['count']);
    }

    #[Test]
    public function generate_computes_total_overdue_correctly(): void
    {
        $this->createOverdueAR(15, 100, 40); // overdue = 60
        $this->createOverdueAR(45, 200, 0);  // overdue = 200

        $result = $this->service->generate($this->tenant->id);

        $this->assertEquals(260.0, $result['summary']['total_overdue']);
    }

    #[Test]
    public function generate_does_not_include_non_overdue_receivables(): void
    {
        // Paid receivable should not appear
        AccountReceivable::create([
            'tenant_id'       => $this->tenant->id,
            'issue_date'      => Carbon::today()->subDays(10),
            'due_date'        => Carbon::today()->subDays(10),
            'amount'          => 500,
            'amount_received' => 500,
            'status'          => 'pago',
            'description'     => 'Paid',
        ]);

        $result = $this->service->generate($this->tenant->id);

        $this->assertEquals(0, $result['summary']['total_records']);
    }

    #[Test]
    public function generate_returns_drill_down_when_client_filter_provided(): void
    {
        $plan   = Plan::factory()->create();
        $tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->createOverdueAR(20, 300, 0, $client->id);

        $result = $this->service->generate($this->tenant->id, $client->id);

        $this->assertNotNull($result['drill_down']);
        $this->assertCount(1, $result['drill_down']);
        $this->assertEquals(300.0, $result['drill_down'][0]['amount']);
    }

    #[Test]
    public function generate_returns_null_drill_down_without_filter(): void
    {
        $this->createOverdueAR(20, 100);

        $result = $this->service->generate($this->tenant->id);

        $this->assertNull($result['drill_down']);
    }

    #[Test]
    public function generate_isolates_by_tenant(): void
    {
        $otherTenant = Tenant::factory()->create(['plan_id' => Plan::factory()->create()->id]);

        AccountReceivable::create([
            'tenant_id'   => $otherTenant->id,
            'issue_date'  => Carbon::today()->subDays(10),
            'due_date'    => Carbon::today()->subDays(10),
            'amount'      => 9999,
            'status'      => 'vencido',
            'description' => 'Other tenant',
        ]);

        $result = $this->service->generate($this->tenant->id);

        $this->assertEquals(0, $result['summary']['total_records']);
    }
}
