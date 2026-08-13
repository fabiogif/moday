<?php

namespace Tests\Feature\Distribtec\Integration;

use App\Models\Category;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Casos de uso integrados: cadastro → listagem → edição dos módulos principais.
 */
class CrudModulesUseCaseTest extends TestCase
{
    use GrantsDistribtecPermissions;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function uc_vendas_cadastro_listagem_e_edicao(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 25.00,
        ]);
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $create = $this->withHeaders($this->auth())->postJson('/api/sale-orders', [
            'client_id' => $client->id,
            'status' => 'orcamento',
            'payment_term_days' => 30,
            'notes' => 'Pedido inicial',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'unit_price' => 25.00,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $create->assertStatus(201);
        $orderId = $create->json('data.id');

        $this->withHeaders($this->auth())
            ->getJson('/api/sale-orders')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $orderId]);

        $this->withHeaders($this->auth())
            ->putJson("/api/sale-orders/{$orderId}", ['notes' => 'Pedido revisado'])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', 'Pedido revisado');

        $this->assertDatabaseHas('sale_orders', [
            'id' => $orderId,
            'tenant_id' => $this->tenant->id,
            'notes' => 'Pedido revisado',
        ]);
    }

    #[Test]
    public function uc_produtos_cadastro_listagem_e_edicao(): void
    {
        $category = Category::factory()->create(['tenant_id' => $this->tenant->id]);

        $create = $this->withHeaders($this->auth())->postJson('/api/product', [
            'name' => 'Produto UC Teste',
            'description' => 'Descrição inicial',
            'price' => 99.90,
            'qtd_stock' => 10,
            'is_active' => true,
            'categories' => [$category->uuid],
        ]);

        $create->assertStatus(201);
        $productId = $create->json('data.id');

        $this->withHeaders($this->auth())
            ->getJson('/api/product')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $productId]);

        $this->withHeaders($this->auth())
            ->putJson("/api/product/{$productId}", [
                'name' => 'Produto UC Atualizado',
                'price' => 129.90,
                'qtd_stock' => 20,
                'is_active' => true,
                'categories' => [$category->uuid],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Produto UC Atualizado');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Produto UC Atualizado',
            'price' => 129.90,
        ]);
    }

    #[Test]
    public function uc_clientes_cadastro_listagem_e_edicao(): void
    {
        $create = $this->withHeaders($this->auth())->postJson('/api/clients', [
            'company_name' => 'Clínica Vida LTDA',
            'trade_name' => 'Clínica Vida',
            'cnpj' => '11.222.333/0001-81',
            'client_type' => 'clinica',
            'credit_limit' => 3000,
            'payment_term_days' => 15,
        ]);

        $create->assertStatus(201);
        $clientId = $create->json('data.id');

        $this->withHeaders($this->auth())
            ->getJson('/api/clients')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $clientId]);

        $this->withHeaders($this->auth())
            ->putJson("/api/clients/{$clientId}", [
                'company_name' => 'Clínica Vida Atualizada',
                'credit_limit' => 4500,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.company_name', 'Clínica Vida Atualizada');
    }

    #[Test]
    public function uc_fornecedores_cadastro_listagem_e_edicao(): void
    {
        $create = $this->withHeaders($this->auth())->postJson('/api/suppliers', [
            'company_name' => 'Distribuidora Pharmex LTDA',
            'trade_name' => 'Pharmex',
            'cnpj' => '11.222.333/0001-81',
            'phone' => '11987654321',
            'payment_term_days' => 30,
            'lead_time_days' => 5,
            'is_active' => true,
        ]);

        $create->assertStatus(201);
        $uuid = $create->json('data.uuid');

        $this->withHeaders($this->auth())
            ->getJson('/api/suppliers')
            ->assertStatus(200)
            ->assertJsonFragment(['uuid' => $uuid]);

        $this->withHeaders($this->auth())
            ->putJson("/api/suppliers/{$uuid}", [
                'company_name' => 'Pharmex Atualizada',
                'payment_term_days' => 45,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Pharmex Atualizada');
    }

    #[Test]
    public function uc_compras_cadastro_listagem_e_edicao(): void
    {
        $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $create = $this->withHeaders($this->auth())->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'expected_delivery' => now()->addDays(10)->format('Y-m-d'),
            'payment_term_days' => 30,
            'notes' => 'Compra inicial',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 40.00,
                ],
            ],
        ]);

        $create->assertStatus(201);
        $orderId = $create->json('data.id');

        $this->withHeaders($this->auth())
            ->getJson('/api/purchase-orders')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $orderId]);

        $this->withHeaders($this->auth())
            ->putJson("/api/purchase-orders/{$orderId}", ['notes' => 'Compra revisada'])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', 'Compra revisada');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $orderId,
            'notes' => 'Compra revisada',
        ]);
    }

    #[Test]
    public function uc_compras_nao_permite_edicao_apos_confirmacao(): void
    {
        $order = PurchaseOrder::factory()->confirmado()->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders($this->auth())
            ->putJson("/api/purchase-orders/{$order->id}", ['notes' => 'Tentativa inválida'])
            ->assertStatus(422);

        $this->assertDatabaseMissing('purchase_orders', [
            'id' => $order->id,
            'notes' => 'Tentativa inválida',
        ]);
    }

    #[Test]
    public function uc_vendas_nao_permite_exclusao_apos_aprovacao(): void
    {
        $order = SaleOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'aprovado',
        ]);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/sale-orders/{$order->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('sale_orders', ['id' => $order->id]);
    }
}
