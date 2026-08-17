<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OfflineSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfflineSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private OfflineSyncService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OfflineSyncService::class);
        $plan          = Plan::factory()->create();
        $this->tenant  = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->user    = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function buildOrder(string $offlineId = 'off-1', array $overrides = []): array
    {
        $client  = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $payment = PaymentMethod::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'qtd_stock' => 100]);

        return array_merge([
            'offline_id'        => $offlineId,
            'client_id'         => $client->id,
            'payment_method_id' => $payment->id,
            'total'             => 100.00,
            'products'          => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 50.00],
            ],
        ], $overrides);
    }

    #[Test]
    public function syncOrders_creates_sale_order_and_items(): void
    {
        $orderData = $this->buildOrder('off-1');

        $results = $this->service->syncOrders($this->tenant->id, $this->user->id, [$orderData]);

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertEquals('off-1', $results[0]['offline_id']);
        $this->assertNotNull($results[0]['order_id']);

        $this->assertDatabaseHas('sale_orders', [
            'id'        => $results[0]['order_id'],
            'tenant_id' => $this->tenant->id,
            'status'    => 'aprovado',
            'total'     => 100.00,
        ]);

        $this->assertDatabaseHas('sale_order_items', [
            'sale_order_id' => $results[0]['order_id'],
            'quantity'      => 2,
            'unit_price'    => 50.00,
        ]);
    }

    #[Test]
    public function syncOrders_processes_batch_and_returns_per_item_results(): void
    {
        $order1 = $this->buildOrder('off-1');
        $order2 = $this->buildOrder('off-2');

        $results = $this->service->syncOrders($this->tenant->id, $this->user->id, [$order1, $order2]);

        $this->assertCount(2, $results);
        $this->assertEquals('off-1', $results[0]['offline_id']);
        $this->assertEquals('off-2', $results[1]['offline_id']);
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);
    }

    #[Test]
    public function syncOrders_marks_failed_item_without_aborting_batch(): void
    {
        $goodOrder = $this->buildOrder('off-1');
        // Bad order: non-existent client_id will cause FK failure
        $badOrder = $this->buildOrder('off-2', ['client_id' => 99999]);

        $results = $this->service->syncOrders($this->tenant->id, $this->user->id, [$goodOrder, $badOrder]);

        $successes = collect($results)->where('success', true)->count();
        $failures  = collect($results)->where('success', false)->count();

        $this->assertEquals(1, $successes);
        $this->assertEquals(1, $failures);
        $this->assertEquals('off-2', collect($results)->where('success', false)->first()['offline_id']);
    }

    #[Test]
    public function countSynced_returns_correct_count(): void
    {
        $results = [
            ['offline_id' => 'a', 'success' => true],
            ['offline_id' => 'b', 'success' => false],
            ['offline_id' => 'c', 'success' => true],
        ];

        $this->assertEquals(2, $this->service->countSynced($results));
    }

    #[Test]
    public function syncOrders_rolls_back_failed_order_transaction(): void
    {
        $beforeCount = SaleOrder::where('tenant_id', $this->tenant->id)->count();

        $badOrder = $this->buildOrder('off-fail', ['client_id' => 99999]);

        $this->service->syncOrders($this->tenant->id, $this->user->id, [$badOrder]);

        $afterCount = SaleOrder::where('tenant_id', $this->tenant->id)->count();

        $this->assertEquals($beforeCount, $afterCount);
    }
}
