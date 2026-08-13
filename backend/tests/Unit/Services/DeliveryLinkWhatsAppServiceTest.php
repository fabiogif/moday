<?php

namespace Tests\Unit\Services;

use App\Models\Driver;
use App\Models\Plan;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DeliveryLinkWhatsAppService;
use App\Services\EvolutionApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryLinkWhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_formats_phone_and_enables_link_preview(): void
    {
        config([
            'services.evolution_api.url' => 'http://evolution.test',
            'services.evolution_api.key' => 'test-key',
        ]);

        Http::fake([
            'http://evolution.test/message/sendText/*' => Http::response(['key' => ['id' => 'msg-1']], 200),
        ]);

        $plan = Plan::factory()->create(['has_whatsapp_notifications' => true]);
        $tenant = Tenant::factory()->create([
            'plan_id'            => $plan->id,
            'evolution_instance' => 'distribuidora-1',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $driver = Driver::create([
            'tenant_id' => $tenant->id,
            'name'      => 'João Motorista',
            'phone'     => '(71) 98888-7777',
            'is_active' => true,
        ]);
        $shipment = Shipment::create([
            'tenant_id'      => $tenant->id,
            'driver_id'      => $driver->id,
            'status'         => 'dispatched',
            'delivery_token' => 'token-abc',
            'identify'       => 'ROM-TEST01',
            'created_by'     => $user->id,
        ]);

        $service = new DeliveryLinkWhatsAppService(new EvolutionApiService());
        $service->send($shipment, 'https://dist.albatec.com.br');

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['number'] === '5571988887777'
                && ($body['linkPreview'] ?? false) === true
                && str_contains($body['text'], 'https://dist.albatec.com.br/delivery/token-abc');
        });
    }

    public function test_send_fails_when_driver_has_no_phone(): void
    {
        $plan = Plan::factory()->create(['has_whatsapp_notifications' => true]);
        $tenant = Tenant::factory()->create([
            'plan_id'            => $plan->id,
            'evolution_instance' => 'distribuidora-1',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $driver = Driver::create([
            'tenant_id' => $tenant->id,
            'name'      => 'João Motorista',
            'phone'     => null,
            'is_active' => true,
        ]);
        $shipment = Shipment::create([
            'tenant_id'      => $tenant->id,
            'driver_id'      => $driver->id,
            'status'         => 'dispatched',
            'delivery_token' => 'token-abc',
            'identify'       => 'ROM-TEST02',
            'created_by'     => $user->id,
        ]);

        $service = new DeliveryLinkWhatsAppService(new EvolutionApiService());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Motorista sem telefone cadastrado.');

        $service->send($shipment, 'https://dist.albatec.com.br');
    }
}
