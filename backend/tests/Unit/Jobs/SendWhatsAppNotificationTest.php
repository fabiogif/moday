<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendWhatsAppNotification;
use App\Models\Client;
use App\Models\Order;
use App\Models\Tenant;
use App\Services\EvolutionApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(bool $optIn): Order
    {
        config(['services.evolution_api.url' => 'http://evolution.test', 'services.evolution_api.key' => 'k']);

        $tenant = Tenant::factory()->create([
            'name'               => 'Moday Restaurante',
            'phone'              => '(71) 3333-4444',
            'evolution_instance' => 'restaurante-1',
        ]);
        $client = Client::factory()->create(['tenant_id' => $tenant->id, 'name' => 'João', 'phone' => '(71) 98888-7777']);

        return Order::factory()->create([
            'tenant_id'              => $tenant->id,
            'client_id'              => $client->id,
            'whatsapp_notifications' => $optIn,
        ]);
    }

    public function test_new_order_message_has_restaurant_phone_and_thanks(): void
    {
        Http::fake(['http://evolution.test/*' => Http::response(['key' => ['id' => 'm1']], 200)]);

        (new SendWhatsAppNotification($this->makeOrder(true), 'new_order'))->handle(new EvolutionApiService());

        Http::assertSent(fn ($request) =>
            $request->data()['number'] === '5571988887777'
            && str_contains($request->data()['text'], 'Moday Restaurante:* (71) 3333-4444')
            && str_contains($request->data()['text'], 'Muito obrigado pelo seu pedido'));
    }

    public function test_skips_when_client_opted_out(): void
    {
        Http::fake();

        (new SendWhatsAppNotification($this->makeOrder(false), 'new_order'))->handle(new EvolutionApiService());

        Http::assertNothingSent();
    }

    public function test_new_order_goes_to_the_phone_and_name_typed_for_the_order(): void
    {
        Http::fake(['http://evolution.test/*' => Http::response(['key' => ['id' => 'm1']], 200)]);
        $order = $this->makeOrder(true);
        $order->update(['customer_name' => 'João Novo', 'customer_phone' => '71911112222']);

        (new SendWhatsAppNotification($order->fresh(), 'new_order'))->handle(new EvolutionApiService());

        Http::assertSent(fn ($request) =>
            $request->data()['number'] === '5571911112222'
            && str_contains($request->data()['text'], 'Olá, João Novo!'));
    }
}
