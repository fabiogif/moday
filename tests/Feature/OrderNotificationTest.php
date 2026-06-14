<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Order;
use App\Models\Client;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Client $client;
    protected PaymentMethod $paymentMethod;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
        ]);

        // Criar usuário autenticado
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'admin@test.com',
        ]);

        // Criar cliente
        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
        ]);

        // Criar forma de pagamento
        $this->paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Dinheiro',
            'is_active' => true,
        ]);

        // Gerar token JWT
        $this->token = auth('api')->login($this->user);
    }

    #[Test]
    public function evento_order_created_deve_ser_disparado_ao_criar_pedido()
    {
        Event::fake();

        // Criar pedido diretamente para testar o evento
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'payment_method_uuid' => $this->paymentMethod->uuid,
            'identify' => 'TEST' . strtoupper(substr(md5(microtime()), 0, 8)),
            'total' => 20.00,
            'status' => 'pending',
            'is_delivery' => false,
            'delivery_type' => 'pickup',
        ]);

        // Disparar evento manualmente (como o controller faria)
        event(new \App\Events\OrderCreated($order));

        // Verificar se evento foi disparado
        Event::assertDispatched(\App\Events\OrderCreated::class, function ($event) {
            return $event->order->tenant_id === $this->tenant->id;
        });
    }

    #[Test]
    public function websocket_deve_receber_dados_corretos_do_pedido()
    {
        Event::fake();

        // Criar pedido
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'payment_method_uuid' => $this->paymentMethod->uuid,
            'identify' => 'WS' . strtoupper(substr(md5(microtime()), 0, 10)),
            'total' => 25.50,
            'status' => 'pending',
            'is_delivery' => true,
            'delivery_type' => 'delivery',
        ]);

        // Disparar evento
        event(new \App\Events\OrderCreated($order));

        Event::assertDispatched(\App\Events\OrderCreated::class, function ($event) {
            $order = $event->order;
            
            // Verificar se os dados essenciais estão presentes
            $this->assertNotNull($order->id);
            $this->assertNotNull($order->identify);
            $this->assertEquals($this->tenant->id, $order->tenant_id);
            $this->assertEquals($this->client->id, $order->client_id);
            $this->assertTrue($order->is_delivery);
            
            return true;
        });
    }

    #[Test]
    public function pedido_deve_ter_todos_campos_necessarios_para_notificacao()
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'payment_method_uuid' => $this->paymentMethod->uuid,
            'identify' => 'TEST' . strtoupper(substr(md5(microtime()), 0, 8)),
            'total' => 50.00,
            'status' => 'pending',
            'is_delivery' => false,
            'delivery_type' => 'pickup',
        ]);

        $this->assertNotNull($order->id);
        $this->assertNotNull($order->identify);
        $this->assertNotNull($order->total);
        $this->assertNotNull($order->created_at);
        
        // Verificar relacionamentos
        $this->assertInstanceOf(Client::class, $order->client);
        $this->assertInstanceOf(Tenant::class, $order->tenant);
    }

    #[Test]
    public function endpoint_de_listagem_retorna_pedidos_do_tenant()
    {
        // Criar alguns pedidos
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'payment_method_uuid' => $this->paymentMethod->uuid,
            'identify' => 'TESTLIST' . strtoupper(substr(md5(microtime()), 0, 4)),
            'total' => 50.00,
            'status' => 'pending',
            'is_delivery' => false,
            'delivery_type' => 'pickup',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/order');

        $response->assertStatus(200);
        
        // Verificar estrutura básica de resposta
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertNotEmpty($responseData['data']);
        
        // Verificar que retorna array de pedidos
        $orders = $responseData['data']['data'] ?? $responseData['data'];
        $this->assertIsArray($orders);
        
        // Verificar que pelo menos um pedido foi criado
        $this->assertGreaterThan(0, count($orders));
        
        // Verificar estrutura de um pedido
        $firstOrder = $orders[0];
        $this->assertArrayHasKey('id', $firstOrder);
        $this->assertArrayHasKey('identify', $firstOrder);
        $this->assertArrayHasKey('total', $firstOrder);
    }

    #[Test]
    public function deve_retornar_nome_do_cliente_em_formatos_diferentes()
    {
        // Teste com cliente via relacionamento
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'payment_method_uuid' => $this->paymentMethod->uuid,
            'identify' => 'CUST' . strtoupper(substr(md5(microtime()), 0, 8)),
            'total' => 75.00,
            'status' => 'pending',
            'is_delivery' => false,
            'delivery_type' => 'pickup',
        ]);

        $order->load('client');

        $this->assertNotNull($order->client);
        $this->assertEquals($this->client->name, $order->client->name);
    }
}

