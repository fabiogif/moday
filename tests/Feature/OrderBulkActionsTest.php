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

class OrderBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected OrderStatus $initialStatus;
    protected OrderStatus $concludedStatus;
    protected OrderStatus $finalStatus;
    protected Client $client;
    protected Product $product;
    protected Table $table;
    protected PaymentMethod $paymentMethod;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::factory()->create();

        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Gerar token JWT
        $this->token = JWTAuth::fromUser($this->user);

        // Criar status inicial
        $this->initialStatus = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pedido Recebido',
            'is_initial' => true,
            'is_final' => false,
            'is_active' => true,
            'order_position' => 1,
        ]);

        // Criar status "Concluído"
        $this->concludedStatus = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Concluído',
            'is_initial' => false,
            'is_final' => true,
            'is_active' => true,
            'order_position' => 4,
        ]);

        // Criar status final para testes
        $this->finalStatus = OrderStatus::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Entregue',
            'is_initial' => false,
            'is_final' => true,
            'is_active' => true,
            'order_position' => 5,
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

    /**
     * Teste: Exclusão em massa - deve excluir múltiplos pedidos com sucesso
     */
    public function test_bulk_delete_orders_success(): void
    {
        // Criar 3 pedidos
        $order1 = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 50.00,
            'identify' => 'ORD001',
        ]);

        $order2 = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 75.00,
            'identify' => 'ORD002',
        ]);

        $order3 = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 100.00,
            'identify' => 'ORD003',
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Executar exclusão em massa
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-delete', [
            'order_ids' => [$order1->identify, $order2->identify, $order3->identify],
        ]);

        // Verificar resposta
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $responseData = $response->json('data');
        $this->assertEquals(3, $responseData['total_deleted']);
        $this->assertCount(3, $responseData['deleted']);

        // Verificar que os pedidos foram excluídos
        $this->assertDatabaseMissing('orders', ['id' => $order1->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order2->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order3->id]);
    }

    /**
     * Teste: Exclusão em massa - deve ignorar pedidos de outros tenants
     */
    public function test_bulk_delete_orders_ignores_other_tenants(): void
    {
        // Criar tenant e pedido de outro tenant
        $otherTenant = Tenant::factory()->create();
        $otherOrder = Order::factory()->create([
            'tenant_id' => $otherTenant->id,
            'identify' => 'ORD-OTHER',
            'total' => 50.00,
        ]);

        // Criar pedido do tenant atual
        $myOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 50.00,
            'identify' => 'ORD-MINE',
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Tentar excluir ambos
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-delete', [
            'order_ids' => [$myOrder->identify, $otherOrder->identify],
        ]);

        // Verificar resposta
        $response->assertStatus(200);
        $responseData = $response->json('data');
        
        // Deve excluir apenas o pedido do tenant atual
        $this->assertEquals(1, $responseData['total_deleted']);
        $this->assertCount(1, $responseData['deleted']);
        $this->assertCount(1, $responseData['unauthorized']);

        // Verificar que apenas o pedido do tenant atual foi excluído
        $this->assertDatabaseMissing('orders', ['id' => $myOrder->id]);
        $this->assertDatabaseHas('orders', ['id' => $otherOrder->id]);
    }

    /**
     * Teste: Exclusão em massa - deve retornar erro se lista vazia
     */
    public function test_bulk_delete_orders_fails_with_empty_list(): void
    {
        $this->actingAs($this->user, 'api');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-delete', [
            'order_ids' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_ids']);
    }

    /**
     * Teste: Exclusão em massa - deve retornar erro se order_ids não for array
     */
    public function test_bulk_delete_orders_fails_with_invalid_format(): void
    {
        $this->actingAs($this->user, 'api');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-delete', [
            'order_ids' => 'not-an-array',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_ids']);
    }

    /**
     * Teste: Atualização de status em massa - deve atualizar múltiplos pedidos para "Concluído"
     */
    public function test_bulk_update_status_to_concluded_success(): void
    {
        // Criar 3 pedidos
        $order1 = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 50.00,
            'identify' => 'ORD001',
        ]);

        $order2 = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 75.00,
            'identify' => 'ORD002',
        ]);

        $order3 = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 100.00,
            'identify' => 'ORD003',
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Executar atualização em massa
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-update-status', [
            'order_ids' => [$order1->identify, $order2->identify, $order3->identify],
            'status' => 'Concluído',
        ]);

        // Verificar resposta
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $responseData = $response->json('data');
        $this->assertEquals(3, $responseData['total_updated']);
        $this->assertCount(3, $responseData['updated']);

        // Verificar que os pedidos foram atualizados
        $order1->refresh();
        $order2->refresh();
        $order3->refresh();

        $this->assertEquals('Concluído', $order1->status);
        $this->assertEquals('Concluído', $order2->status);
        $this->assertEquals('Concluído', $order3->status);
        $this->assertEquals($this->concludedStatus->id, $order1->order_status_id);
        $this->assertEquals($this->concludedStatus->id, $order2->order_status_id);
        $this->assertEquals($this->concludedStatus->id, $order3->order_status_id);
    }

    /**
     * Teste: Atualização de status em massa - deve ignorar pedidos com status final
     */
    public function test_bulk_update_status_ignores_final_status_orders(): void
    {
        // Criar pedido com status final
        $finalOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Entregue',
            'order_status_id' => $this->finalStatus->id,
            'total' => 50.00,
            'identify' => 'ORD-FINAL',
        ]);

        // Criar pedido sem status final
        $normalOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 50.00,
            'identify' => 'ORD-NORMAL',
        ]);

        // Autenticar usuário
        $this->actingAs($this->user, 'api');

        // Tentar atualizar ambos
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-update-status', [
            'order_ids' => [$finalOrder->identify, $normalOrder->identify],
            'status' => 'Concluído',
        ]);

        // Verificar resposta
        $response->assertStatus(200);
        $responseData = $response->json('data');
        
        // Deve atualizar apenas o pedido sem status final
        $this->assertEquals(1, $responseData['total_updated']);
        $this->assertCount(1, $responseData['updated']);
        $this->assertCount(1, $responseData['final_status']);

        // Verificar que apenas o pedido normal foi atualizado
        $normalOrder->refresh();
        $this->assertEquals('Concluído', $normalOrder->status);

        $finalOrder->refresh();
        $this->assertEquals('Entregue', $finalOrder->status); // Não deve ter mudado
    }

    /**
     * Teste: Atualização de status em massa - deve retornar erro se status não existir
     */
    public function test_bulk_update_status_fails_with_invalid_status(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 50.00,
            'identify' => 'ORD001',
        ]);

        $this->actingAs($this->user, 'api');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-update-status', [
            'order_ids' => [$order->identify],
            'status' => 'Status Inexistente',
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Teste: Atualização de status em massa - deve retornar erro se lista vazia
     */
    public function test_bulk_update_status_fails_with_empty_list(): void
    {
        $this->actingAs($this->user, 'api');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-update-status', [
            'order_ids' => [],
            'status' => 'Concluído',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_ids']);
    }

    /**
     * Teste: Atualização de status em massa - deve retornar erro se status não informado
     */
    public function test_bulk_update_status_fails_without_status(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 50.00,
            'identify' => 'ORD001',
        ]);

        $this->actingAs($this->user, 'api');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-update-status', [
            'order_ids' => [$order->identify],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    /**
     * Teste: Exclusão em massa - deve lidar com pedidos não encontrados
     */
    public function test_bulk_delete_handles_not_found_orders(): void
    {
        // Criar um pedido válido
        $validOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 50.00,
            'identify' => 'ORD-VALID',
        ]);

        $this->actingAs($this->user, 'api');

        // Tentar excluir pedido válido e inexistente
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-delete', [
            'order_ids' => [$validOrder->identify, 'NONEXISTENT'],
        ]);

        $response->assertStatus(200);
        $responseData = $response->json('data');
        
        // Deve excluir apenas o pedido válido
        $this->assertEquals(1, $responseData['total_deleted']);
        $this->assertCount(1, $responseData['deleted']);
        $this->assertCount(1, $responseData['not_found']);
    }

    /**
     * Teste: Atualização de status em massa - deve lidar com pedidos não encontrados
     */
    public function test_bulk_update_status_handles_not_found_orders(): void
    {
        // Criar um pedido válido
        $validOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'table_id' => $this->table->id,
            'status' => 'Pedido Recebido',
            'order_status_id' => $this->initialStatus->id,
            'total' => 50.00,
            'identify' => 'ORD-VALID',
        ]);

        $this->actingAs($this->user, 'api');

        // Tentar atualizar pedido válido e inexistente
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/orders/bulk-update-status', [
            'order_ids' => [$validOrder->identify, 'NONEXISTENT'],
            'status' => 'Concluído',
        ]);

        $response->assertStatus(200);
        $responseData = $response->json('data');
        
        // Deve atualizar apenas o pedido válido
        $this->assertEquals(1, $responseData['total_updated']);
        $this->assertCount(1, $responseData['updated']);
        $this->assertCount(1, $responseData['not_found']);
    }

    /**
     * Teste: Exclusão em massa - requer autenticação
     */
    public function test_bulk_delete_requires_authentication(): void
    {
        $response = $this->postJson('/api/orders/bulk-delete', [
            'order_ids' => ['ORD001'],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Teste: Atualização de status em massa - requer autenticação
     */
    public function test_bulk_update_status_requires_authentication(): void
    {
        $response = $this->postJson('/api/orders/bulk-update-status', [
            'order_ids' => ['ORD001'],
            'status' => 'Concluído',
        ]);

        $response->assertStatus(401);
    }
}

