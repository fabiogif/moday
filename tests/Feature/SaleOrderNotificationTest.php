<?php

namespace Tests\Feature;

use App\Events\SaleOrderCreated;
use App\Models\Client;
use App\Models\Plan;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Sale\SaleOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SaleOrderNotificationTest extends TestCase
{
    use RefreshDatabase;
    use GrantsDistribtecPermissions;

    private User $user;
    private Tenant $tenant;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cliente Notificação',
        ]);
    }

    #[Test]
    public function criar_pedido_pelo_painel_dispara_sale_order_created(): void
    {
        Event::fake([SaleOrderCreated::class]);

        $service = app(SaleOrderService::class);
        $order = $service->create($this->tenant->id, $this->user->id, [
            'client_id' => $this->client->id,
            'status' => 'orcamento',
            'payment_method' => 'boleto',
        ], []);

        Event::assertDispatched(SaleOrderCreated::class, function (SaleOrderCreated $event) use ($order) {
            return $event->saleOrder->id === $order->id
                && $event->saleOrder->tenant_id === $this->tenant->id;
        });
    }

    #[Test]
    public function broadcast_de_sale_order_usa_canal_e_payload_do_painel(): void
    {
        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'total' => 99.90,
        ]);

        $event = new SaleOrderCreated($order->fresh(['client']));

        $this->assertSame('order.created', $event->broadcastAs());
        $this->assertSame(
            'private-tenant.' . $this->tenant->id . '.orders',
            $event->broadcastOn()[0]->name
        );

        $payload = $event->broadcastWith();
        $this->assertSame('sale_order', $payload['order']['source']);
        $this->assertSame('/sale-orders/' . $order->id, $payload['order']['href']);
        $this->assertSame($order->identify, $payload['order']['identify']);
        $this->assertSame('Cliente Notificação', $payload['order']['customer_name']);
    }

    #[Test]
    public function api_store_dispara_evento_de_notificacao(): void
    {
        Event::fake([SaleOrderCreated::class]);

        $token = JWTAuth::fromUser($this->user);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/sale-orders', [
            'client_id' => $this->client->id,
            'status' => 'orcamento',
            'payment_method' => 'pix',
        ])->assertStatus(201);

        Event::assertDispatched(SaleOrderCreated::class);
    }

    #[Test]
    public function pedido_offline_existente_nao_redispara_evento(): void
    {
        Event::fake([SaleOrderCreated::class]);

        $service = app(SaleOrderService::class);
        $first = $service->create($this->tenant->id, $this->user->id, [
            'offline_id' => 'offline-abc-1',
            'client_id' => $this->client->id,
            'status' => 'orcamento',
        ], []);

        Event::assertDispatchedTimes(SaleOrderCreated::class, 1);

        $second = $service->create($this->tenant->id, $this->user->id, [
            'offline_id' => 'offline-abc-1',
            'client_id' => $this->client->id,
            'status' => 'orcamento',
        ], []);

        $this->assertSame($first->id, $second->id);
        Event::assertDispatchedTimes(SaleOrderCreated::class, 1);
    }
}
