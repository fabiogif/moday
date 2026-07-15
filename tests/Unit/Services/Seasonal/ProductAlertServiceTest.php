<?php

namespace Tests\Unit\Services\Seasonal;

use App\Models\Batch;
use App\Models\Product;
use App\Models\SeasonalAlert;
use App\Models\Tenant;
use App\Services\Seasonal\ProductAlertService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductAlertService $service;
    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(ProductAlertService::class);
        $this->tenantId = Tenant::factory()->create()->id;
    }

    public function test_generates_critical_alert_for_batch_expiring_in_7_days(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);
        Batch::factory()->create([
            'tenant_id'          => $this->tenantId,
            'product_id'         => $product->id,
            'expiry_date'        => Carbon::today()->addDays(5),
            'quantity_available' => 100,
            'status'             => 'available',
        ]);

        $count = $this->service->generateBatchAlerts($this->tenantId);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('seasonal_alerts', [
            'tenant_id' => $this->tenantId,
            'type'      => 'expiring_lot',
            'priority'  => 'critical',
        ]);
    }

    public function test_generates_promotion_suggestion_for_lot_expiring_in_15_days(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);
        Batch::factory()->create([
            'tenant_id'          => $this->tenantId,
            'product_id'         => $product->id,
            'expiry_date'        => Carbon::today()->addDays(10),
            'quantity_available' => 200,
            'status'             => 'available',
        ]);

        $this->service->generateBatchAlerts($this->tenantId);

        $this->assertDatabaseHas('seasonal_alerts', [
            'tenant_id' => $this->tenantId,
            'type'      => 'promotion_suggestion',
        ]);
    }

    public function test_generates_critical_alert_for_expired_batch_with_stock(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);
        Batch::factory()->create([
            'tenant_id'          => $this->tenantId,
            'product_id'         => $product->id,
            'expiry_date'        => Carbon::today()->subDays(3),
            'quantity_available' => 50,
            'status'             => 'available',
        ]);

        $count = $this->service->generateBatchAlerts($this->tenantId);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('seasonal_alerts', [
            'tenant_id' => $this->tenantId,
            'type'      => 'expired_lot',
            'priority'  => 'critical',
        ]);
    }

    public function test_skips_duplicate_alerts_for_same_batch(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);
        Batch::factory()->create([
            'tenant_id'          => $this->tenantId,
            'product_id'         => $product->id,
            'expiry_date'        => Carbon::today()->addDays(5),
            'quantity_available' => 100,
            'status'             => 'available',
        ]);

        $this->service->generateBatchAlerts($this->tenantId);
        $firstCount  = SeasonalAlert::forTenant($this->tenantId)->count();

        $this->service->generateBatchAlerts($this->tenantId);
        $secondCount = SeasonalAlert::forTenant($this->tenantId)->count();

        $this->assertEquals($firstCount, $secondCount);
    }

    public function test_acknowledge_marks_alert_as_seen(): void
    {
        $user = \App\Models\User::factory()->create(['tenant_id' => $this->tenantId]);

        $alert = SeasonalAlert::create([
            'tenant_id' => $this->tenantId,
            'type'      => 'expiring_lot',
            'priority'  => 'high',
            'title'     => 'Test Alert',
        ]);

        $updated = $this->service->acknowledge($this->tenantId, $alert->id, $user->id);

        $this->assertNotNull($updated->acknowledged_at);
        $this->assertEquals($user->id, $updated->acknowledged_by);
    }

    public function test_summary_counts_by_type_and_priority(): void
    {
        SeasonalAlert::create(['tenant_id' => $this->tenantId, 'type' => 'expiring_lot',  'priority' => 'critical', 'title' => 'A']);
        SeasonalAlert::create(['tenant_id' => $this->tenantId, 'type' => 'expired_lot',   'priority' => 'critical', 'title' => 'B']);
        SeasonalAlert::create(['tenant_id' => $this->tenantId, 'type' => 'expiring_lot',  'priority' => 'high',     'title' => 'C']);

        $summary = $this->service->summary($this->tenantId);

        $this->assertEquals(3, $summary['total_unacknowledged']);
        $this->assertEquals(2, $summary['critical']);
        $this->assertArrayHasKey('expiring_lot', $summary['by_type']);
    }
}
