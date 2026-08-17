<?php

namespace Tests\Feature;

use App\Models\SaleOrder;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Logistics\DeliveryRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use App\Http\Middleware\Authenticate as AppAuthenticate;
use Illuminate\Auth\Middleware\Authenticate as LaravelAuthenticate;

class DeliveryRouteApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'address' => 'Av Paulista, 1000',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01310-100',
        ]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->withoutMiddleware([
            JWTAuthenticate::class,
            AppAuthenticate::class,
            LaravelAuthenticate::class,
            \App\Http\Middleware\CheckPlanFeatures::class,
            \App\Http\Middleware\PermissionMiddleware::class,
        ]);
        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_show_shipment_detail(): void
    {
        $shipment = Shipment::create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'route_name' => 'Zona Sul',
        ]);

        $response = $this->getJson("/api/shipments/{$shipment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.identify', $shipment->identify)
            ->assertJsonPath('data.route_name', 'Zona Sul');
    }

    #[Test]
    public function it_optimizes_route_with_heuristic_fallback(): void
    {
        Config::set('services.google_maps.api_key', null);

        $shipment = Shipment::create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'faturado',
            'shipping_zipcode' => '01310-100',
            'delivery_window_start' => '09:00:00',
            'delivery_window_end' => '12:00:00',
        ]);

        DB::table('shipment_sale_order')->insert([
            'shipment_id' => $shipment->id,
            'sale_order_id' => $order->id,
            'delivery_window_start' => '09:00:00',
            'delivery_window_end' => '12:00:00',
        ]);

        $result = app(DeliveryRouteService::class)->optimizeRoute($shipment->fresh(['saleOrders.client', 'tenant']));

        $this->assertSame('estimated', $result['provider']);
        $this->assertGreaterThan(0, $result['estimated_km']);
        $this->assertGreaterThan(0, $result['estimated_duration_minutes']);
        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
        ]);
    }

    #[Test]
    public function it_sets_delivery_window_on_pivot(): void
    {
        $shipment = Shipment::create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
        ]);

        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'faturado',
        ]);

        DB::table('shipment_sale_order')->insert([
            'shipment_id' => $shipment->id,
            'sale_order_id' => $order->id,
        ]);

        $response = $this->patchJson("/api/deliveries/{$shipment->id}/orders/{$order->id}/window", [
            'window_start' => '10:00',
            'window_end' => '13:00',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('shipment_sale_order', [
            'shipment_id' => $shipment->id,
            'sale_order_id' => $order->id,
            'delivery_window_start' => '10:00:00',
            'delivery_window_end' => '13:00:00',
        ]);
    }

    #[Test]
    public function it_reorders_stops_manually_and_marks_source(): void
    {
        Config::set('services.google_maps.api_key', null);

        $shipment = Shipment::create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'route_order_source' => 'system',
        ]);

        $orderA = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'faturado',
            'shipping_zipcode' => '01310-100',
            'shipping_city' => 'São Paulo',
            'shipping_state' => 'SP',
        ]);
        $orderB = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'faturado',
            'shipping_zipcode' => '04038-001',
            'shipping_city' => 'São Paulo',
            'shipping_state' => 'SP',
        ]);

        DB::table('shipment_sale_order')->insert([
            [
                'shipment_id' => $shipment->id,
                'sale_order_id' => $orderA->id,
                'delivery_sequence' => 1,
            ],
            [
                'shipment_id' => $shipment->id,
                'sale_order_id' => $orderB->id,
                'delivery_sequence' => 2,
            ],
        ]);

        $response = $this->postJson("/api/deliveries/{$shipment->id}/reorder-stops", [
            'sale_order_ids' => [$orderB->id, $orderA->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.route_order_source', 'manual')
            ->assertJsonPath('data.optimized_route.0.sale_order_id', $orderB->id)
            ->assertJsonPath('data.optimized_route.1.sale_order_id', $orderA->id);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'route_order_source' => 'manual',
        ]);
        $this->assertDatabaseHas('shipment_sale_order', [
            'shipment_id' => $shipment->id,
            'sale_order_id' => $orderB->id,
            'delivery_sequence' => 1,
        ]);
        $this->assertDatabaseHas('shipment_sale_order', [
            'shipment_id' => $shipment->id,
            'sale_order_id' => $orderA->id,
            'delivery_sequence' => 2,
        ]);
    }

    #[Test]
    public function it_rejects_reorder_when_shipment_is_not_draft(): void
    {
        $shipment = Shipment::create([
            'tenant_id' => $this->tenant->id,
            'status' => 'dispatched',
        ]);

        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'faturado',
        ]);

        DB::table('shipment_sale_order')->insert([
            'shipment_id' => $shipment->id,
            'sale_order_id' => $order->id,
        ]);

        $response = $this->postJson("/api/deliveries/{$shipment->id}/reorder-stops", [
            'sale_order_ids' => [$order->id],
        ]);

        $response->assertStatus(422);
    }
}
