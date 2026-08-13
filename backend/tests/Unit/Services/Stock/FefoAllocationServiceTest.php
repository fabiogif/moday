<?php

namespace Tests\Unit\Services\Stock;

use App\Exceptions\StockException;
use App\Models\Batch;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\Stock\FefoAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FefoAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FefoAllocationService $service;
    private Tenant $tenant;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service   = app(FefoAllocationService::class);
        $plan            = Plan::factory()->create();
        $this->tenant    = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->product   = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function allocate_prioritizes_earliest_expiry_first(): void
    {
        $later = Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $this->product->id,
            'warehouse_id'       => $this->warehouse->id,
            'status'             => 'available',
            'quantity_available' => 100,
            'quantity_reserved'  => 0,
            'expiry_date'        => now()->addDays(40)->toDateString(),
            'batch_number'       => 'LATE',
        ]);

        $sooner = Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $this->product->id,
            'warehouse_id'       => $this->warehouse->id,
            'status'             => 'available',
            'quantity_available' => 100,
            'quantity_reserved'  => 0,
            'expiry_date'        => now()->addDays(10)->toDateString(),
            'batch_number'       => 'SOON',
        ]);

        $allocations = $this->service->allocate(
            $this->tenant->id,
            $this->product->id,
            50,
            $this->warehouse->id,
        );

        $this->assertCount(1, $allocations);
        $this->assertEquals($sooner->id, $allocations[0]['batch_id']);
        $this->assertEquals(50.0, $allocations[0]['quantity']);
        $this->assertNotEquals($later->id, $allocations[0]['batch_id']);
    }

    #[Test]
    public function allocate_skips_expired_batches(): void
    {
        Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $this->product->id,
            'warehouse_id'       => $this->warehouse->id,
            'status'             => 'available',
            'quantity_available' => 80,
            'quantity_reserved'  => 0,
            'expiry_date'        => now()->subDays(2)->toDateString(),
            'batch_number'       => 'EXPIRED',
        ]);

        $valid = Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $this->product->id,
            'warehouse_id'       => $this->warehouse->id,
            'status'             => 'available',
            'quantity_available' => 80,
            'quantity_reserved'  => 0,
            'expiry_date'        => now()->addDays(20)->toDateString(),
            'batch_number'       => 'VALID',
        ]);

        $allocations = $this->service->allocate(
            $this->tenant->id,
            $this->product->id,
            30,
        );

        $this->assertCount(1, $allocations);
        $this->assertEquals($valid->id, $allocations[0]['batch_id']);
    }

    #[Test]
    public function allocate_places_null_expiry_after_dated_batches(): void
    {
        $undated = Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $this->product->id,
            'warehouse_id'       => $this->warehouse->id,
            'status'             => 'available',
            'quantity_available' => 100,
            'quantity_reserved'  => 0,
            'expiry_date'        => null,
            'batch_number'       => 'NO-EXP',
        ]);

        $dated = Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $this->product->id,
            'warehouse_id'       => $this->warehouse->id,
            'status'             => 'available',
            'quantity_available' => 40,
            'quantity_reserved'  => 0,
            'expiry_date'        => now()->addDays(15)->toDateString(),
            'batch_number'       => 'WITH-EXP',
        ]);

        $allocations = $this->service->allocate(
            $this->tenant->id,
            $this->product->id,
            50,
        );

        $this->assertCount(2, $allocations);
        $this->assertEquals($dated->id, $allocations[0]['batch_id']);
        $this->assertEquals(40.0, $allocations[0]['quantity']);
        $this->assertEquals($undated->id, $allocations[1]['batch_id']);
        $this->assertEquals(10.0, $allocations[1]['quantity']);
    }

    #[Test]
    public function allocate_throws_when_insufficient_stock(): void
    {
        Batch::factory()->create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $this->product->id,
            'warehouse_id'       => $this->warehouse->id,
            'status'             => 'available',
            'quantity_available' => 5,
            'quantity_reserved'  => 0,
            'expiry_date'        => now()->addDays(30)->toDateString(),
        ]);

        $this->expectException(StockException::class);

        $this->service->allocate($this->tenant->id, $this->product->id, 20);
    }
}
