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

    /**
     * Reproduz o bug de produção: getNextStatus() casava o status atual contra
     * uma lista fixa de nomes em português ("Pendente", "Preparo", "Entrega"...),
     * a mesma nomenclatura de DefaultOrderStatusesSeeder. Um tenant que renomeia
     * seus status (ex.: "Recebido", "Preparando") não batia com nenhum padrão e
     * o avanço falhava com "Não há próximo status disponível". O fluxo agora
     * segue apenas order_position, então funciona com qualquer nomenclatura.
     */
    public function test_advances_order_with_custom_tenant_status_names(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $token = JWTAuth::fromUser($user);
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id]);

        $statuses = [];
        foreach ([
            ['name' => 'Recebido', 'position' => 1, 'is_initial' => true, 'is_final' => false],
            ['name' => 'Preparando', 'position' => 2, 'is_initial' => false, 'is_final' => false],
            ['name' => 'Entrega', 'position' => 3, 'is_initial' => false, 'is_final' => false],
            ['name' => 'Concluído', 'position' => 4, 'is_initial' => false, 'is_final' => true],
            ['name' => 'Cancelado', 'position' => 5, 'is_initial' => false, 'is_final' => true],
        ] as $data) {
            $statuses[$data['name']] = OrderStatus::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'slug' => \Illuminate\Support\Str::slug($data['name']),
                'order_position' => $data['position'],
                'is_initial' => $data['is_initial'],
                'is_final' => $data['is_final'],
                'is_active' => true,
            ]);
        }

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'status' => 'Recebido',
            'order_status_id' => $statuses['Recebido']->id,
            'is_delivery' => false,
            'total' => 50.00,
        ]);
        $order->products()->attach($product->id, ['qty' => 1, 'price' => 50.00]);

        $response = $this->withToken($token)
            ->postJson("/api/order/{$order->identify}/advance-status");

        $response->assertOk()->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame('Preparando', $order->status);
        $this->assertSame($statuses['Preparando']->id, $order->order_status_id);
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
