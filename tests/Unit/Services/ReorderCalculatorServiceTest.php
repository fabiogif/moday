<?php

namespace Tests\Unit\Services;

use App\Models\Plan;
use App\Models\Product;
use App\Models\ReorderSuggestion;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\ReorderSuggestionRepository;
use App\Services\ReorderCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReorderCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReorderCalculatorService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $repo          = new ReorderSuggestionRepository();
        $this->service = new ReorderCalculatorService($repo);
        $plan          = Plan::factory()->create();
        $this->tenant  = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user    = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function generateForTenant_creates_suggestion_for_product_with_sales(): void
    {
        $product = Product::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'is_active'  => true,
            'qtd_stock'  => 5,
            'min_stock'  => 10,
            'price_cost' => 20.00,
        ]);

        // 60 units sold in last 30 days → 2/day average
        $order = SaleOrder::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'status'     => 'faturado',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        SaleOrderItem::create([
            'sale_order_id' => $order->id,
            'product_id'    => $product->id,
            'item_type'     => 'venda',
            'quantity'      => 60,
            'unit_price'    => 10.00,
            'subtotal'      => 600.00,
        ]);

        $created = $this->service->generateForTenant($this->tenant->id);

        $this->assertGreaterThan(0, $created);

        $suggestion = ReorderSuggestion::where('tenant_id', $this->tenant->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($suggestion);
        $this->assertGreaterThan(0, $suggestion->daily_avg_sales);
        $this->assertContains($suggestion->urgency, ['critical', 'urgent', 'normal']);
    }

    #[Test]
    public function generateForTenant_skips_product_with_no_sales(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 100,
        ]);

        $created = $this->service->generateForTenant($this->tenant->id);

        $this->assertEquals(0, $created);
    }

    #[Test]
    public function urgency_is_critical_when_coverage_below_lead_time(): void
    {
        // stock=2, dailyAvg=2 → coverage=1 day < leadTime=7 → critical
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 2,
        ]);

        $order = SaleOrder::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'status'     => 'faturado',
            'created_at' => Carbon::now()->subDays(1),
        ]);

        SaleOrderItem::create([
            'sale_order_id' => $order->id,
            'product_id'    => $product->id,
            'item_type'     => 'venda',
            'quantity'      => 60,
            'unit_price'    => 10.00,
            'subtotal'      => 600.00,
        ]);

        $this->service->generateForTenant($this->tenant->id);

        $suggestion = ReorderSuggestion::where('product_id', $product->id)->first();

        $this->assertEquals('critical', $suggestion->urgency);
    }

    #[Test]
    public function listPending_returns_only_undismissed_suggestions(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        ReorderSuggestion::create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $product->id,
            'is_dismissed'       => false,
            'urgency'            => 'normal',
            'daily_avg_sales'    => 1,
            'suggested_quantity' => 30,
            'calculated_at'      => now(),
        ]);

        ReorderSuggestion::create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => Product::factory()->create(['tenant_id' => $this->tenant->id])->id,
            'is_dismissed'       => true,
            'urgency'            => 'urgent',
            'daily_avg_sales'    => 2,
            'suggested_quantity' => 60,
            'calculated_at'      => now(),
        ]);

        $pending = $this->service->listPending($this->tenant->id);

        $this->assertCount(1, $pending);
        $this->assertFalse((bool) $pending->first()->is_dismissed);
    }

    #[Test]
    public function dismiss_marks_suggestion_as_dismissed(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $suggestion = ReorderSuggestion::create([
            'tenant_id'          => $this->tenant->id,
            'product_id'         => $product->id,
            'is_dismissed'       => false,
            'urgency'            => 'normal',
            'daily_avg_sales'    => 1,
            'suggested_quantity' => 30,
            'calculated_at'      => now(),
        ]);

        $this->service->dismiss($this->tenant->id, $suggestion->id);

        $this->assertDatabaseHas('reorder_suggestions', [
            'id'          => $suggestion->id,
            'is_dismissed' => true,
        ]);
    }
}
