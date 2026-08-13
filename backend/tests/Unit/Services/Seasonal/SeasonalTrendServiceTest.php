<?php

namespace Tests\Unit\Services\Seasonal;

use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Seasonal\SeasonalTrendService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonalTrendServiceTest extends TestCase
{
    use RefreshDatabase;

    private SeasonalTrendService $service;
    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(SeasonalTrendService::class);
        $this->tenantId = Tenant::factory()->create()->id;
    }

    public function test_monthly_revenue_trend_returns_correct_months(): void
    {
        $user   = User::factory()->create(['tenant_id' => $this->tenantId]);
        $client = \App\Models\Client::factory()->create(['tenant_id' => $this->tenantId]);

        SaleOrder::factory()->create([
            'tenant_id'  => $this->tenantId,
            'status'     => 'faturado',
            'total'      => 500.00,
            'billed_at'  => Carbon::now()->subMonths(2),
            'client_id'  => $client->id,
        ]);

        SaleOrder::factory()->create([
            'tenant_id'  => $this->tenantId,
            'status'     => 'entregue',
            'total'      => 800.00,
            'billed_at'  => Carbon::now()->subMonths(1),
            'client_id'  => $client->id,
        ]);

        $trend = $this->service->monthlyRevenueTrend($this->tenantId, 1);

        $this->assertGreaterThanOrEqual(2, $trend->count());
        $this->assertArrayHasKey('revenue', $trend->first());
        $this->assertArrayHasKey('month_name', $trend->first());
    }

    public function test_seasonality_index_marks_peak_months(): void
    {
        $client = \App\Models\Client::factory()->create(['tenant_id' => $this->tenantId]);

        // Create orders in December (traditionally a peak month)
        for ($i = 0; $i < 3; $i++) {
            SaleOrder::factory()->create([
                'tenant_id' => $this->tenantId,
                'status'    => 'faturado',
                'total'     => 2000.00,
                'billed_at' => Carbon::createFromDate(date('Y') - 1, 12, 15),
                'client_id' => $client->id,
            ]);
        }

        // And low months
        SaleOrder::factory()->create([
            'tenant_id' => $this->tenantId,
            'status'    => 'faturado',
            'total'     => 100.00,
            'billed_at' => Carbon::createFromDate(date('Y') - 1, 6, 15),
            'client_id' => $client->id,
        ]);

        $index = $this->service->seasonalityIndex($this->tenantId, 2);

        $this->assertNotEmpty($index);
        $december = $index->firstWhere('month', 12);
        $this->assertNotNull($december);
        $this->assertArrayHasKey('is_peak', $december);
        $this->assertArrayHasKey('index', $december);
    }

    public function test_product_demand_by_month_returns_top_products(): void
    {
        $client  = \App\Models\Client::factory()->create(['tenant_id' => $this->tenantId]);
        $product = Product::factory()->create(['tenant_id' => $this->tenantId]);

        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenantId,
            'status'    => 'faturado',
            'total'     => 1000.00,
            'billed_at' => Carbon::createFromDate(date('Y') - 1, 12, 10),
            'client_id' => $client->id,
        ]);

        SaleOrderItem::create([
            'sale_order_id' => $order->id,
            'product_id'    => $product->id,
            'item_type'     => 'venda',
            'quantity'      => 5,
            'unit_price'    => 200.00,
            'subtotal'      => 1000.00,
        ]);

        $demand = $this->service->productDemandByMonth($this->tenantId, 12, 2);

        $this->assertGreaterThanOrEqual(1, $demand->count());
        $this->assertArrayHasKey('total_revenue', $demand->first());
        $this->assertArrayHasKey('product_name', $demand->first());
    }

    public function test_monthly_trend_excludes_non_final_statuses(): void
    {
        $client = \App\Models\Client::factory()->create(['tenant_id' => $this->tenantId]);

        SaleOrder::factory()->create([
            'tenant_id' => $this->tenantId,
            'status'    => 'orcamento', // should be excluded
            'total'     => 999.00,
            'billed_at' => Carbon::now()->subMonths(1),
            'client_id' => $client->id,
        ]);

        $trend = $this->service->monthlyRevenueTrend($this->tenantId, 1);

        $this->assertEquals(0, $trend->sum('revenue'));
    }
}
