<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Tenant;
use Database\Seeders\DefaultOrderStatusesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultOrderStatusesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_new_flow_statuses_with_integration_codes(): void
    {
        $tenant = Tenant::factory()->create();

        $this->seed(DefaultOrderStatusesSeeder::class);

        $this->assertDatabaseCount('order_statuses', count(DefaultOrderStatusesSeeder::definitions()));

        $initial = OrderStatus::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_initial', true)
            ->first();

        $this->assertNotNull($initial);
        $this->assertSame('pendente', $initial->slug);
        $this->assertSame('Pendente', $initial->name);
        $this->assertContains('PLACED', $initial->integration_codes['ifood'] ?? []);

        $names = OrderStatus::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('order_position')
            ->pluck('name')
            ->all();

        $this->assertSame(
            ['Pendente', 'Aceito', 'Preparo', 'Entrega', 'Concluído', 'Cancelado'],
            $names
        );
    }

    public function test_seeder_is_idempotent_by_slug(): void
    {
        $tenant = Tenant::factory()->create();

        $this->seed(DefaultOrderStatusesSeeder::class);
        $this->seed(DefaultOrderStatusesSeeder::class);

        $active = OrderStatus::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        $this->assertSame(count(DefaultOrderStatusesSeeder::definitions()), $active);
    }

    public function test_seeder_remaps_legacy_slugs_and_order_names(): void
    {
        $tenant = Tenant::factory()->create();

        $legacy = OrderStatus::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Pedido Recebido',
            'slug' => 'pedido-recebido',
            'is_initial' => true,
            'is_final' => false,
            'order_position' => 1,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $legacy->id,
        ]);

        $this->seed(DefaultOrderStatusesSeeder::class);

        $order->refresh();
        $this->assertSame('Pendente', $order->status);

        $pendente = OrderStatus::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'pendente')
            ->first();

        $this->assertNotNull($pendente);
        $this->assertSame($pendente->id, $order->order_status_id);
    }
}
