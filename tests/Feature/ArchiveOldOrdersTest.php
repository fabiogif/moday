<?php

namespace Tests\Feature;

use App\Jobs\ArchiveOldOrders;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ArchiveOldOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_archives_orders_older_than_one_year(): void
    {
        Log::spy();

        $plan = Plan::factory()->create();
        $tenant = Tenant::factory()->create(['plan_id' => $plan->id]);

        $oldOrder = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Em Preparo',
            'created_at' => now()->subYears(2),
        ]);

        $recentOrder = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Em Preparo',
            'created_at' => now()->subMonths(6),
        ]);

        $alreadyArchived = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => Order::STATUS_ARCHIVED,
            'archived_at' => now()->subMonths(3),
            'created_at' => now()->subYears(2),
        ]);

        (new ArchiveOldOrders())->handle();

        $oldOrder->refresh();
        $recentOrder->refresh();
        $alreadyArchived->refresh();

        $this->assertEquals(Order::STATUS_ARCHIVED, $oldOrder->status);
        $this->assertNotNull($oldOrder->archived_at);

        $this->assertEquals('Em Preparo', $recentOrder->status);
        $this->assertNull($recentOrder->archived_at);

        $this->assertEquals(Order::STATUS_ARCHIVED, $alreadyArchived->status);
        $this->assertNotNull($alreadyArchived->archived_at);

        Log::shouldHaveReceived('info')->withArgs(function (string $message) {
            return str_contains($message, 'ArchiveOldOrders job concluído');
        })->once();
    }
}


