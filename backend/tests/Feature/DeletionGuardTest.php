<?php

namespace Tests\Feature;

use App\Models\{User, Tenant, Product, Category, Client, Table, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Desabilita middlewares para focar na regra de negócio
        $this->withoutMiddleware();
    }

    private function actingUserWithTenant(): array
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        return [$user, $tenant];
    }

    #[Test]
    public function bloqueia_exclusao_de_produto_em_pedido_em_preparo(): void
    {
        [$user, $tenant] = $this->actingUserWithTenant();

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Em Preparo',
        ]);

        // Vincular produto ao pedido
        $order->products()->attach($product->id, [
            'qty' => 1,
            'price' => $product->price ?? 10,
        ]);

        $response = $this->deleteJson('/api/product/'.$product->id);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Produto não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'orders'
                ]
            ]);
    }

    #[Test]
    public function bloqueia_exclusao_de_categoria_com_produto_ativo_vinculado(): void
    {
        [$user, $tenant] = $this->actingUserWithTenant();

        $category = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        // Vincular produto ativo à categoria
        $product->categories()->attach($category->id);

        $response = $this->deleteJson('/api/category/'.$category->uuid);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'linked_products'
                ]
            ]);
    }

    #[Test]
    public function bloqueia_exclusao_de_cliente_com_pedido_em_preparo(): void
    {
        [$user, $tenant] = $this->actingUserWithTenant();

        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Em Preparo',
            'client_id' => $client->id,
        ]);

        $response = $this->deleteJson('/api/client/'.$client->id);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Cliente não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'orders'
                ]
            ]);
    }

    #[Test]
    public function bloqueia_exclusao_de_mesa_com_pedido_em_preparo(): void
    {
        [$user, $tenant] = $this->actingUserWithTenant();

        $table = \App\Models\Table::create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'name' => 'Mesa 01',
            'identify' => 'TBL01',
            'capacity' => 4,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Em Preparo',
            'table_id' => $table->id,
        ]);

        $response = $this->deleteJson('/api/table/'.$table->uuid);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Mesa não pode ser excluída, existe um pedido ativo ou não arquivado vinculado.',
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'orders'
                ]
            ]);
    }
}


