<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendRestaurantOrderWhatsApp;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Tenant;
use App\Services\EvolutionApiService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendRestaurantOrderWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(bool $planHasWhatsApp = true): SaleOrder
    {
        config([
            'services.evolution_api.url' => 'http://evolution.test',
            'services.evolution_api.key' => 'test-key',
        ]);

        $plan   = Plan::factory()->create(['has_whatsapp_notifications' => $planHasWhatsApp]);
        $tenant = Tenant::factory()->create([
            'plan_id'            => $plan->id,
            'evolution_instance' => 'restaurante-1',
            'phone'              => '(71) 3333-4444',
        ]);
        $client  = Client::factory()->create(['tenant_id' => $tenant->id, 'name' => 'João']);
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Hambúrguer']);

        $order = SaleOrder::factory()->create([
            'tenant_id'      => $tenant->id,
            'client_id'      => $client->id,
            'total'          => 40,
            'payment_method' => 'pix',
            'notes'          => 'Sem cebola',
        ]);
        SaleOrderItem::create([
            'sale_order_id'    => $order->id,
            'product_id'       => $product->id,
            'item_type'        => 'venda',
            'quantity'         => 2,
            'unit_price'       => 20,
            'discount_percent' => 0,
            'subtotal'         => 40,
            'tax_amount'       => 0,
        ]);

        return $order;
    }

    private function handleJob(SaleOrder $order): void
    {
        (new SendRestaurantOrderWhatsApp($order))->handle(new EvolutionApiService(), app(WhatsAppService::class));
    }

    public function test_sends_order_to_restaurant_phone(): void
    {
        Http::fake(['http://evolution.test/*' => Http::response(['key' => ['id' => 'm1']], 200)]);
        $order = $this->makeOrder();

        $this->handleJob($order);

        Http::assertSent(function ($request) use ($order) {
            $body = $request->data();
            return str_ends_with($request->url(), '/message/sendText/restaurante-1')
                && $body['number'] === '557133334444'
                && str_contains($body['text'], "#{$order->identify}")
                && str_contains($body['text'], '2x Hambúrguer')
                && str_contains($body['text'], 'Sem cebola')
                && str_contains($body['text'], 'João');
        });
    }

    public function test_skips_when_plan_has_no_whatsapp(): void
    {
        Http::fake();

        $this->handleJob($this->makeOrder(planHasWhatsApp: false));

        Http::assertNothingSent();
    }

    public function test_throws_on_evolution_failure_so_queue_retries(): void
    {
        Http::fake(['http://evolution.test/*' => Http::response('down', 500)]);

        $this->expectException(\RuntimeException::class);

        $this->handleJob($this->makeOrder());
    }
}
