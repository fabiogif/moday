<?php

namespace Tests\Unit;

use App\Models\OrderStatus;
use App\Models\Tenant;
use Database\Seeders\DefaultOrderStatusesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultOrderStatusesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_statuses_with_integration_codes(): void
    {
        $tenant = Tenant::factory()->create();

        $this->seed(DefaultOrderStatusesSeeder::class);

        $this->assertDatabaseCount('order_statuses', count(DefaultOrderStatusesSeeder::definitions()));

        $initial = OrderStatus::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_initial', true)
            ->first();

        $this->assertNotNull($initial);
        $this->assertSame('pedido-recebido', $initial->slug);
        $this->assertContains('PLACED', $initial->integration_codes['ifood'] ?? []);
    }

    public function test_seeder_is_idempotent_by_slug(): void
    {
        $tenant = Tenant::factory()->create();

        $this->seed(DefaultOrderStatusesSeeder::class);
        $this->seed(DefaultOrderStatusesSeeder::class);

        $this->assertDatabaseCount('order_statuses', count(DefaultOrderStatusesSeeder::definitions()));
    }
}
