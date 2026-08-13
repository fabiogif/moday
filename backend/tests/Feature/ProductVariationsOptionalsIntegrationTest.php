<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Testes de Integração: Fluxo Completo de Variações e Opcionais
 * Simula o comportamento real do usuário no sistema
 */
class ProductVariationsOptionalsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Category $category;
    protected array $authHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        $this->grantFullAccess($this->user, $this->tenant);
        $this->category = Category::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->authHeaders = [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($this->user),
            'Accept' => 'application/json',
        ];

        $this->withHeaders($this->authHeaders);
    }

    #[Test]
    public function fluxo_completo_criar_editar_e_excluir_produto_com_variacoes()
    {
        // PASSO 1: CRIAR produto com variações
        $createData = [
            'name' => 'Pizza Test',
            'description' => 'Pizza de teste',
            'price' => 30.00,
            'price_cost' => 15.00,
            'qtd_stock' => 50,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequena', 'price' => -5.00],
                ['id' => 'v2', 'name' => 'Grande', 'price' => 10.00],
            ],
        ];

        $createResponse = $this->postJson('/api/product', $createData);
        $createResponse->assertStatus(201);
        
        $productId = $createResponse->json('data.identify');
        $this->assertNotNull($productId);

        // PASSO 2: VERIFICAR se foi criado corretamente
        $showResponse = $this->getJson("/api/product/{$productId}");
        $showResponse->assertStatus(200)
                    ->assertJsonPath('data.variations.0.name', 'Pequena')
                    ->assertJsonPath('data.variations.1.name', 'Grande');

        // PASSO 3: EDITAR - Mudar nome de uma variação e adicionar outra
        $updateData = [
            'name' => 'Pizza Test',
            'description' => 'Pizza de teste',
            'price' => 30.00,
            'price_cost' => 15.00,
            'qtd_stock' => 50,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequena Editada', 'price' => -3.00], // Editado
                ['id' => 'v2', 'name' => 'Grande', 'price' => 10.00],
                ['id' => 'v3', 'name' => 'Extra Grande', 'price' => 15.00],    // Adicionado
            ],
        ];

        $updateResponse = $this->postJson("/api/product/{$productId}", $updateData + ['_method' => 'PUT']);
        $updateResponse->assertStatus(200);

        // PASSO 4: VERIFICAR edição
        $showAfterUpdate = $this->getJson("/api/product/{$productId}");
        $showAfterUpdate->assertStatus(200)
                       ->assertJsonPath('data.variations.0.name', 'Pequena Editada');
        
        $this->assertEquals(-3.00, $showAfterUpdate->json('data.variations.0.price'));
        
        $this->assertCount(3, $showAfterUpdate->json('data.variations'));

        // PASSO 5: REMOVER todas as variações
        $removeData = [
            'name' => 'Pizza Test',
            'description' => 'Pizza de teste',
            'price' => 30.00,
            'price_cost' => 15.00,
            'qtd_stock' => 50,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [], // Removendo
        ];

        $removeResponse = $this->postJson("/api/product/{$productId}", $removeData + ['_method' => 'PUT']);
        $removeResponse->assertStatus(200);

        // PASSO 6: VERIFICAR remoção
        $showAfterRemove = $this->getJson("/api/product/{$productId}");
        $showAfterRemove->assertStatus(200)
                       ->assertJsonPath('data.variations', []);

        // PASSO 7: DELETAR produto
        $deleteResponse = $this->deleteJson("/api/product/{$productId}");
        $deleteResponse->assertStatus(204);

        $this->assertSoftDeleted('products', ['uuid' => $productId]);
    }

    #[Test]
    public function fluxo_completo_criar_editar_e_excluir_produto_com_opcionais()
    {
        // CRIAR
        $createData = [
            'name' => 'Burger Test',
            'description' => 'Hambúrguer de teste',
            'price' => 20.00,
            'price_cost' => 10.00,
            'qtd_stock' => 30,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
                ['id' => 'o2', 'name' => 'Queijo', 'price' => 3.00],
            ],
        ];

        $createResponse = $this->postJson('/api/product', $createData);
        $createResponse->assertStatus(201);
        
        $productId = $createResponse->json('data.identify');

        // EDITAR
        $updateData = [
            'name' => 'Burger Test',
            'description' => 'Hambúrguer de teste',
            'price' => 20.00,
            'price_cost' => 10.00,
            'qtd_stock' => 30,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon Premium', 'price' => 7.00],  // Editado
                ['id' => 'o3', 'name' => 'Ovo', 'price' => 2.50],            // Adicionado (o2 removido)
            ],
        ];

        $updateResponse = $this->postJson("/api/product/{$productId}", $updateData + ['_method' => 'PUT']);
        $updateResponse->assertStatus(200);

        // VERIFICAR
        $product = Product::where('uuid', $productId)->first();
        $this->assertCount(2, $product->optionals);
        $this->assertEquals('Bacon Premium', $product->optionals[0]['name']);
        $this->assertEquals('Ovo', $product->optionals[1]['name']);
    }

    #[Test]
    public function fluxo_completo_produto_com_ambos_variacoes_e_opcionais()
    {
        // CRIAR produto completo
        $createData = [
            'name' => 'Produto Completo',
            'description' => 'Com variações e opcionais',
            'price' => 25.00,
            'price_cost' => 12.00,
            'qtd_stock' => 40,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
                ['id' => 'v2', 'name' => 'Grande', 'price' => 8.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Extra 1', 'price' => 3.00],
                ['id' => 'o2', 'name' => 'Extra 2', 'price' => 4.00],
            ],
        ];

        $createResponse = $this->postJson('/api/product', $createData);
        $createResponse->assertStatus(201);
        
        $productId = $createResponse->json('data.identify');

        // EDITAR - Remover uma variação, adicionar um opcional
        $updateData = [
            'name' => 'Produto Completo',
            'description' => 'Com variações e opcionais',
            'price' => 25.00,
            'price_cost' => 12.00,
            'qtd_stock' => 40,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
                // v2 removido
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Extra 1', 'price' => 3.00],
                ['id' => 'o2', 'name' => 'Extra 2', 'price' => 4.00],
                ['id' => 'o3', 'name' => 'Extra 3', 'price' => 5.00], // Adicionado
            ],
        ];

        $updateResponse = $this->postJson("/api/product/{$productId}", $updateData + ['_method' => 'PUT']);
        $updateResponse->assertStatus(200);

        // VERIFICAR
        $product = Product::where('uuid', $productId)->first();
        $this->assertCount(1, $product->variations);
        $this->assertCount(3, $product->optionals);

        // REMOVER TUDO
        $removeAllData = [
            'name' => 'Produto Completo',
            'description' => 'Com variações e opcionais',
            'price' => 25.00,
            'price_cost' => 12.00,
            'qtd_stock' => 40,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [],
            'optionals' => [],
        ];

        $removeResponse = $this->postJson("/api/product/{$productId}", $removeAllData + ['_method' => 'PUT']);
        $removeResponse->assertStatus(200);

        // VERIFICAR REMOÇÃO
        $productAfterRemove = Product::where('uuid', $productId)->first();
        $this->assertEmpty($productAfterRemove->variations);
        $this->assertEmpty($productAfterRemove->optionals);
    }

    #[Test]
    public function persiste_corretamente_apos_multiplas_edicoes()
    {
        // Criar produto
        $product = Product::factory()->for($this->tenant)->create([
            'variations' => [
                ['id' => 'v1', 'name' => 'Original', 'price' => 5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        // Edição 1
        $this->postJson("/api/product/{$product->id}", [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Editado 1', 'price' => 6.00],
            ],
        ] + ['_method' => 'PUT']);

        // Edição 2
        $this->postJson("/api/product/{$product->id}", [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Editado 2', 'price' => 7.00],
            ],
        ] + ['_method' => 'PUT']);

        // Edição 3
        $this->postJson("/api/product/{$product->id}", [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Final', 'price' => 8.00],
            ],
        ] + ['_method' => 'PUT']);

        // VERIFICAR que a última edição persistiu
        $finalProduct = Product::find($product->id);
        $this->assertEquals('Final', $finalProduct->variations[0]['name']);
        $this->assertEquals(8.00, $finalProduct->variations[0]['price']);
    }

    #[Test]
    public function api_retorna_formato_correto_para_frontend()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'variations' => [
                ['id' => 'v1', 'name' => 'P', 'price' => -5.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $response = $this->getJson("/api/product/{$product->uuid}");

        $response->assertStatus(200);

        $data = $response->json('data');

        if (is_array($data) && isset($data[0])) {
            $data = $data[0];
        }

        $this->assertIsArray($data);

        // Verificar que variations é ARRAY, não STRING
        $this->assertIsArray($data['variations']);
        $this->assertNotNull($data['variations'][0]['id']);
        $this->assertNotNull($data['variations'][0]['name']);
        $this->assertIsNumeric($data['variations'][0]['price']);

        // Verificar que optionals é ARRAY, não STRING
        $this->assertIsArray($data['optionals']);
        $this->assertNotNull($data['optionals'][0]['id']);
        $this->assertNotNull($data['optionals'][0]['name']);
        $this->assertIsNumeric($data['optionals'][0]['price']);
    }
}

