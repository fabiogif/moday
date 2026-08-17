<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Client;
use App\Models\Product;
use App\Models\Table;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderFinalStatusEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected OrderStatus $finalStatus;
    protected OrderStatus $nonFinalStatus;
    protected Client $client;
    protected Product $product;
    protected Table $table;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::factory()->create();

        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar status final
        $this->finalStatus = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Concluído',
            'is_final' => true,
            'is_active' => true,
            'order_position' => 4,
        ]);

        // Criar status não final
        $this->nonFinalStatus = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Em Preparo',
            'is_final' => false,
            'is_active' => true,
            'order_position' => 1,
        ]);

        // Criar cliente
        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar produto
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar mesa
        $this->table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar método de pagamento
        $this->paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . JWTAuth::fromUser($this->user)];
    }

    /**
     * Teste: Não deve permitir atualizar pedido com status final
     */
    public function test_cannot_update_order_with_final_status(): void
    {
        // Criar pedido com status final
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Concluído',
            'order_status_id' => $this->finalStatus->id,
            'total' => 50.00,
        ]);

        // Associar produto ao pedido
        $order->products()->attach($this->product->id, [
            'qty' => 2,
            'price' => 25.00,
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Tentar atualizar o pedido
        $response = $this->withHeaders($this->authHeaders())->putJson("/api/order/{$order->identify}", [
            'comment' => 'Tentativa de atualizar pedido finalizado',
            'products' => [
                [
                    'identify' => $this->product->uuid,
                    'qty' => 3,
                    'price' => 25.00,
                ],
            ],
        ]);

        // Deve retornar 403 (ApiResponseClass sempre envia success: true)
        $response->assertStatus(403);
        $response->assertJson([
            'success' => true,
            'message' => 'Este pedido está finalizado e não pode ser alterado.',
        ]);

        // Verificar que o pedido não foi alterado
        $order->refresh();
        $this->assertEquals('Concluído', $order->status);
        $this->assertEquals($this->finalStatus->id, $order->order_status_id);
    }

    /**
     * Teste: Não deve permitir atualizar pedido com status final (por nome)
     */
    public function test_cannot_update_order_with_final_status_by_name(): void
    {
        $entregueStatus = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Entregue',
            'is_final' => true,
            'is_active' => true,
            'order_position' => 5,
        ]);

        // Criar pedido com status final (sem order_status_id, apenas nome)
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Entregue', // Status final padrão
            'order_status_id' => null,
            'total' => 50.00,
        ]);

        // Associar produto ao pedido
        $order->products()->attach($this->product->id, [
            'qty' => 2,
            'price' => 25.00,
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Tentar atualizar o pedido
        $response = $this->withHeaders($this->authHeaders())->putJson("/api/order/{$order->identify}", [
            'comment' => 'Tentativa de atualizar pedido entregue',
        ]);

        // Deve retornar 403
        $response->assertStatus(403);
        $response->assertJson([
            'success' => true,
            'message' => 'Este pedido está finalizado e não pode ser alterado.',
        ]);
        $this->assertNotNull($entregueStatus->id);
    }

    /**
     * Teste: Deve permitir atualizar pedido sem status final
     */
    public function test_can_update_order_without_final_status(): void
    {
        // Criar pedido sem status final
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Em Preparo',
            'order_status_id' => $this->nonFinalStatus->id,
            'total' => 50.00,
        ]);

        // Associar produto ao pedido
        $order->products()->attach($this->product->id, [
            'qty' => 2,
            'price' => 25.00,
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Atualizar o pedido
        $response = $this->withHeaders($this->authHeaders())->putJson("/api/order/{$order->identify}", [
            'comment' => 'Observação atualizada',
            'products' => [
                [
                    'identify' => $this->product->uuid,
                    'qty' => 3,
                    'price' => 25.00,
                ],
            ],
        ]);

        // Deve retornar sucesso
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Pedido atualizado com sucesso',
        ]);

        // Verificar que o pedido foi atualizado
        $order->refresh();
        $this->assertEquals('Observação atualizada', $order->comment);
    }

    /**
     * Teste: Não deve permitir atualizar pedido cancelado
     */
    public function test_cannot_update_cancelled_order(): void
    {
        OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cancelado',
            'is_final' => true,
            'is_active' => true,
            'order_position' => 6,
        ]);

        // Criar pedido cancelado
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Cancelado', // Status final padrão
            'order_status_id' => null,
            'total' => 50.00,
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Tentar atualizar o pedido
        $response = $this->withHeaders($this->authHeaders())->putJson("/api/order/{$order->identify}", [
            'comment' => 'Tentativa de atualizar pedido cancelado',
        ]);

        // Deve retornar erro 403
        $response->assertStatus(403);
        $response->assertJson([
            'success' => true,
            'message' => 'Este pedido está finalizado e não pode ser alterado.',
        ]);
    }

    /**
     * Teste: Não deve permitir atualizar pedido arquivado
     */
    public function test_cannot_update_archived_order(): void
    {
        OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Arquivado',
            'is_final' => true,
            'is_active' => true,
            'order_position' => 7,
        ]);

        // Criar pedido arquivado
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Arquivado', // Status final padrão
            'order_status_id' => null,
            'total' => 50.00,
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Tentar atualizar o pedido
        $response = $this->withHeaders($this->authHeaders())->putJson("/api/order/{$order->identify}", [
            'comment' => 'Tentativa de atualizar pedido arquivado',
        ]);

        // Deve retornar erro 403
        $response->assertStatus(403);
        $response->assertJson([
            'success' => true,
            'message' => 'Este pedido está finalizado e não pode ser alterado.',
        ]);
    }

    /**
     * Teste: Deve verificar status final mesmo quando tentar forçar atualização
     */
    public function test_cannot_force_update_final_order_by_changing_status(): void
    {
        // Criar pedido com status final
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Concluído',
            'order_status_id' => $this->finalStatus->id,
            'total' => 50.00,
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Tentar atualizar o pedido tentando mudar o status
        $response = $this->withHeaders($this->authHeaders())->putJson("/api/order/{$order->identify}", [
            'status' => 'Em Preparo', // Tentativa de mudar status
            'comment' => 'Tentativa de forçar atualização',
        ]);

        // Deve retornar erro 403 (a validação ocorre antes de processar os dados)
        $response->assertStatus(403);
        $response->assertJson([
            'success' => true,
            'message' => 'Este pedido está finalizado e não pode ser alterado.',
        ]);
    }
}

