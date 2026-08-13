<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\SaleOrder;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PodPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_large_jpeg_photo_from_mobile_camera(): void
    {
        Storage::fake('public');

        [$shipment, $order] = $this->createDispatchedShipmentWithOrder();

        $photo = UploadedFile::fake()->create('IMG_1234.jpg', 7000, 'image/jpeg');

        $response = $this->post("/api/delivery/{$shipment->delivery_token}/orders/{$order->id}/pod", [
            'status' => 'delivered',
            'photo'  => $photo,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('shipment_sale_order', [
            'shipment_id'   => $shipment->id,
            'sale_order_id' => $order->id,
            'pod_status'    => 'delivered',
        ]);
    }

    public function test_accepts_heic_photo_from_iphone(): void
    {
        Storage::fake('public');

        [$shipment, $order] = $this->createDispatchedShipmentWithOrder();

        $photo = UploadedFile::fake()->create('IMG_5678.heic', 4000, 'image/heic');

        $response = $this->post("/api/delivery/{$shipment->delivery_token}/orders/{$order->id}/pod", [
            'status' => 'delivered',
            'photo'  => $photo,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_rejects_unsupported_photo_format(): void
    {
        Storage::fake('public');

        [$shipment, $order] = $this->createDispatchedShipmentWithOrder();

        $photo = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->post("/api/delivery/{$shipment->delivery_token}/orders/{$order->id}/pod", [
            'status' => 'delivered',
            'photo'  => $photo,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    /** @return array{0: Shipment, 1: SaleOrder} */
    private function createDispatchedShipmentWithOrder(): array
    {
        $user = User::factory()->create();
        $driver = Driver::create([
            'tenant_id' => $user->tenant_id,
            'name'      => 'Motorista Teste',
            'phone'     => '71999998888',
            'is_active' => true,
        ]);

        $shipment = Shipment::create([
            'tenant_id'      => $user->tenant_id,
            'driver_id'      => $driver->id,
            'status'         => 'dispatched',
            'delivery_token' => Str::random(48),
            'identify'       => 'ROM-TESTPOD',
            'created_by'     => $user->id,
        ]);

        $order = SaleOrder::factory()->create([
            'tenant_id' => $user->tenant_id,
            'status'    => 'em_transito',
        ]);

        $shipment->saleOrders()->attach($order->id);

        return [$shipment, $order];
    }
}
