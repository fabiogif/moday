<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use App\Http\Middleware\Authenticate as AppAuthenticate;
use Illuminate\Auth\Middleware\Authenticate as LaravelAuthenticate;

class FreightWeightApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
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
    public function can_save_and_load_freight_weight_settings(): void
    {
        $this->putJson('/api/logistics/freight-weight', [
            'cf' => 1.20,
            'cv' => 0.80,
            'mkp' => 1.25,
            'charge_mode' => 'per_kg',
        ])->assertOk()
            ->assertJsonPath('data.fp_unit', 2.5)
            ->assertJsonPath('data.unit_label', 'R$/kg');

        $this->getJson('/api/logistics/freight-weight')
            ->assertOk()
            ->assertJsonPath('data.cf', 1.2)
            ->assertJsonPath('data.cv', 0.8)
            ->assertJsonPath('data.mkp', 1.25)
            ->assertJsonPath('data.charge_mode', 'per_kg');
    }

    #[Test]
    public function calculates_freight_weight_per_kg(): void
    {
        $this->putJson('/api/logistics/freight-weight', [
            'cf' => 1.0,
            'cv' => 1.0,
            'mkp' => 1.5,
            'charge_mode' => 'per_kg',
        ])->assertOk();

        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'faturado',
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'weight' => 2.0,
        ]);

        SaleOrderItem::create([
            'sale_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 10,
            'subtotal' => 50,
        ]);

        $shipment = Shipment::create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'created_by' => $this->user->id,
            'total_weight_kg' => 10.0, // 5 × 2kg
        ]);

        DB::table('shipment_sale_order')->insert([
            'shipment_id' => $shipment->id,
            'sale_order_id' => $order->id,
        ]);

        // FP unit = (1+1)*1.5 = 3.0 R$/kg × 10 kg = 30.00
        $this->postJson("/api/deliveries/{$shipment->id}/freight-weight")
            ->assertOk()
            ->assertJsonPath('data.fp_unit', 3)
            ->assertJsonPath('data.quantity', 10)
            ->assertJsonPath('data.total', 30)
            ->assertJsonPath('data.charge_mode', 'per_kg');

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'freight_weight_amount' => 30.00,
            'freight_weight_charge_mode' => 'per_kg',
        ]);
    }

    #[Test]
    public function calculates_freight_weight_per_cte(): void
    {
        $this->putJson('/api/logistics/freight-weight', [
            'cf' => 10,
            'cv' => 5,
            'mkp' => 1.2,
            'charge_mode' => 'per_cte',
        ])->assertOk();

        $orders = SaleOrder::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'faturado',
        ]);

        $shipment = Shipment::create([
            'tenant_id' => $this->tenant->id,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        foreach ($orders as $order) {
            DB::table('shipment_sale_order')->insert([
                'shipment_id' => $shipment->id,
                'sale_order_id' => $order->id,
            ]);
        }

        // FP unit = (10+5)*1.2 = 18 R$/CT-e × 2 CT-e = 36
        $this->postJson("/api/deliveries/{$shipment->id}/freight-weight")
            ->assertOk()
            ->assertJsonPath('data.fp_unit', 18)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.total', 36)
            ->assertJsonPath('data.unit_label', 'R$/CT-e');
    }
}
