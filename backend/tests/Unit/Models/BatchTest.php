<?php

namespace Tests\Unit\Models;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BatchTest extends TestCase
{
    private Tenant $tenant;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $plan           = Plan::factory()->create();
        $this->tenant   = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->product  = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function it_detects_expired_batches(): void
    {
        $batch = Batch::factory()->expired()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
        ]);

        $this->assertTrue($batch->isExpired());
    }

    #[Test]
    public function it_detects_non_expired_batches(): void
    {
        $batch = Batch::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'expiry_date' => now()->addMonths(6),
        ]);

        $this->assertFalse($batch->isExpired());
    }

    #[Test]
    public function it_calculates_days_until_expiry(): void
    {
        $batch = Batch::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'expiry_date' => now()->addDays(30),
        ]);

        $days = $batch->daysUntilExpiry();
        $this->assertNotNull($days);
        $this->assertGreaterThanOrEqual(29, $days);
        $this->assertLessThanOrEqual(31, $days);
    }

    #[Test]
    public function it_returns_negative_days_for_expired_batch(): void
    {
        $batch = Batch::factory()->expired()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
        ]);

        $this->assertLessThan(0, $batch->daysUntilExpiry());
    }

    #[Test]
    public function scope_fefo_returns_available_batches_ordered_by_expiry(): void
    {
        $older = Batch::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'expiry_date' => now()->addDays(20),
            'status'      => 'available',
            'quantity_available' => 100,
        ]);

        $newer = Batch::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'expiry_date' => now()->addDays(60),
            'status'      => 'available',
            'quantity_available' => 100,
        ]);

        $batches = Batch::forTenant($this->tenant->id)->fefo()->get();
        $this->assertTrue($batches->first()->expiry_date->lte($batches->last()->expiry_date));
    }

    #[Test]
    public function scope_expiring_soon_returns_correct_batches(): void
    {
        Batch::factory()->expiringSoon(15)->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'status'      => 'available',
            'quantity_available' => 10,
        ]);

        // This one should NOT appear (expires in 90 days)
        Batch::factory()->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
            'expiry_date' => now()->addDays(90),
            'status'      => 'available',
            'quantity_available' => 10,
        ]);

        $expiring = Batch::forTenant($this->tenant->id)->expiringSoon(30)->get();
        $this->assertCount(1, $expiring);
    }

    #[Test]
    public function scope_for_tenant_filters_correctly(): void
    {
        Batch::factory()->count(2)->create([
            'tenant_id'   => $this->tenant->id,
            'product_id'  => $this->product->id,
            'warehouse_id'=> $this->warehouse->id,
        ]);

        $otherPlan    = Plan::factory()->create();
        $otherTenant  = Tenant::factory()->create(['plan_id' => $otherPlan->id]);
        $otherProduct = Product::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherWH      = Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);

        Batch::factory()->create([
            'tenant_id'   => $otherTenant->id,
            'product_id'  => $otherProduct->id,
            'warehouse_id'=> $otherWH->id,
        ]);

        $this->assertCount(2, Batch::forTenant($this->tenant->id)->get());
    }
}
