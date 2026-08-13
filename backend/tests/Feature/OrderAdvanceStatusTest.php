<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DefaultOrderStatusesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderAdvanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Client $client;

    protected Product $product;

    protected Table $table;

    protected PaymentMethod $paymentMethod;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->token = JWTAuth::fromUser($this->user);

        (new DefaultOrderStatusesSeeder())->run();

        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_advances_delivery_order_from_entrega_to_concluido(): void
    {
        $currentStatus = $this->statusByName('Entrega');
        $expectedNext = $this->statusByName('Concluído');

        $order = $this->createOrder($currentStatus, true);

        $response = $this->withToken($this->token)
            ->postJson("/api/order/{$order->identify}/advance-status");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame('Concluído', $order->status);
        $this->assertSame($expectedNext->id, $order->order_status_id);
    }

    public function test_advances_counter_order_from_entrega_to_concluido(): void
    {
        $currentStatus = $this->statusByName('Entrega');
        $expectedNext = $this->statusByName('Concluído');

        $order = $this->createOrder($currentStatus, false);

        $response = $this->withToken($this->token)
            ->postJson("/api/order/{$order->identify}/advance-status");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame('Concluído', $order->status);
        $this->assertSame($expectedNext->id, $order->order_status_id);
    }

    protected function statusByName(string $name): OrderStatus
    {
        return OrderStatus::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('name', $name)
            ->firstOrFail();
    }

    protected function createOrder(OrderStatus $status, bool $isDelivery): Order
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => $status->name,
            'order_status_id' => $status->id,
            'is_delivery' => $isDelivery,
            'total' => 50.00,
        ]);

        $order->products()->attach($this->product->id, [
            'qty' => 1,
            'price' => 50.00,
        ]);

        return $order;
    }
}
