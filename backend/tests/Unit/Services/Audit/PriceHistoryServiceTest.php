<?php

namespace Tests\Unit\Services\Audit;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\PriceHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private PriceHistoryService $service;
    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(PriceHistoryService::class);
        $this->tenantId = Tenant::factory()->create()->id;
    }

    public function test_records_price_change_on_update(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenantId,
            'price'     => 10.00,
        ]);

        // Preserve attributes ao simular dirty state (setRawAttributes sozinho apaga tenant_id)
        $attrs = $product->getAttributes();
        $attrs['price'] = '10.00';
        $product->setRawAttributes($attrs, true);
        $product->price = 12.50;

        $user = User::factory()->create(['tenant_id' => $this->tenantId]);
        $count = $this->service->recordChanges($product, changedBy: $user->id);

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('price_histories', [
            'tenant_id'  => $this->tenantId,
            'product_id' => $product->id,
            'field'      => 'price',
            'old_value'  => 10.00,
            'new_value'  => 12.50,
            'changed_by' => $user->id,
        ]);
    }

    public function test_skips_unchanged_fields(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenantId,
            'price'     => 10.00,
        ]);

        $attrs = $product->getAttributes();
        $attrs['price'] = '10.00';
        $product->setRawAttributes($attrs, true);
        $product->price = 10.00; // no change

        $count = $this->service->recordChanges($product);

        $this->assertEquals(0, $count);
        $this->assertEquals(0, PriceHistory::forTenant($this->tenantId)->count());
    }

    public function test_records_multiple_price_field_changes(): void
    {
        $product = Product::factory()->create([
            'tenant_id'  => $this->tenantId,
            'price'      => 10.00,
            'price_cost' => 6.00,
        ]);

        $attrs = $product->getAttributes();
        $attrs['price'] = '10.00';
        $attrs['price_cost'] = '6.00';
        $product->setRawAttributes($attrs, true);
        $product->price      = 12.00;
        $product->price_cost = 7.00;

        $user = User::factory()->create(['tenant_id' => $this->tenantId]);
        $count = $this->service->recordChanges($product, changedBy: $user->id);

        $this->assertEquals(2, $count);
    }

    public function test_calculates_percentage_change(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenantId,
            'price'     => 10.00,
        ]);

        $attrs = $product->getAttributes();
        $attrs['price'] = '10.00';
        $product->setRawAttributes($attrs, true);
        $product->price = 12.00;

        $this->service->recordChanges($product);

        $history = PriceHistory::forTenant($this->tenantId)->first();
        $this->assertEquals(20.00, (float) $history->change_pct);
    }

    public function test_list_for_product_returns_paginated_results(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);

        PriceHistory::create(['tenant_id' => $this->tenantId, 'product_id' => $product->id, 'field' => 'price', 'old_value' => 10, 'new_value' => 12]);
        PriceHistory::create(['tenant_id' => $this->tenantId, 'product_id' => $product->id, 'field' => 'price', 'old_value' => 12, 'new_value' => 14]);

        $result = $this->service->listForProduct($this->tenantId, $product->id);

        $this->assertEquals(2, $result->total());
    }

    public function test_volatility_summary_returns_top_products(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);

        PriceHistory::create(['tenant_id' => $this->tenantId, 'product_id' => $product->id, 'field' => 'price', 'old_value' => 10, 'new_value' => 12, 'change_pct' => 20]);
        PriceHistory::create(['tenant_id' => $this->tenantId, 'product_id' => $product->id, 'field' => 'price', 'old_value' => 12, 'new_value' => 15, 'change_pct' => 25]);

        $result = $this->service->summarizeVolatility($this->tenantId, 30);

        $this->assertGreaterThanOrEqual(1, $result->count());
    }
}
