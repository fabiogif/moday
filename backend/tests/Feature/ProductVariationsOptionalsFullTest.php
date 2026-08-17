<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Testes completos de CRUD para Variações e Opcionais de Produtos
 */
class ProductVariationsOptionalsFullTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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

    // ============================================
    // TESTES DE CRIAÇÃO (CREATE)
    // ============================================

    #[Test]
    public function pode_criar_produto_com_variacoes()
    {
        $productData = [
            'name' => 'Pizza Margherita',
            'description' => 'Pizza tradicional italiana',
            'price' => 35.00,
            'price_cost' => 18.00,
            'qtd_stock' => 50,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => json_encode([
                ['id' => 'v1', 'name' => 'Pequena (4 fatias)', 'price' => -5.00],
                ['id' => 'v2', 'name' => 'Média (6 fatias)', 'price' => 0.00],
                ['id' => 'v3', 'name' => 'Grande (8 fatias)', 'price' => 10.00],
            ]),
        ];

        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => ['id', 'identify', 'name', 'variations']
                ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Pizza Margherita',
            'tenant_id' => $this->tenant->id,
        ]);

        $product = Product::where('name', 'Pizza Margherita')->first();
        $this->assertCount(3, $product->variations);
        $this->assertEquals('Pequena (4 fatias)', $product->variations[0]['name']);
        $this->assertEquals(-5.00, $product->variations[0]['price']);
    }

    #[Test]
    public function pode_criar_produto_com_opcionais()
    {
        $productData = [
            'name' => 'X-Burger',
            'description' => 'Hambúrguer artesanal',
            'price' => 22.00,
            'price_cost' => 12.00,
            'qtd_stock' => 30,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon Extra', 'price' => 5.00],
                ['id' => 'o2', 'name' => 'Queijo Cheddar', 'price' => 3.00],
                ['id' => 'o3', 'name' => 'Ovo', 'price' => 2.50],
            ],
        ];

        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(201);

        $product = Product::where('name', 'X-Burger')->first();
        $this->assertCount(3, $product->optionals);
        $this->assertEquals('Bacon Extra', $product->optionals[0]['name']);
        $this->assertEquals(5.00, $product->optionals[0]['price']);
    }

    #[Test]
    public function pode_criar_produto_com_variacoes_e_opcionais()
    {
        $productData = [
            'name' => 'Açaí Premium',
            'description' => 'Açaí batido na hora',
            'price' => 18.00,
            'price_cost' => 10.00,
            'qtd_stock' => 100,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => '300ml', 'price' => -5.00],
                ['id' => 'v2', 'name' => '500ml', 'price' => 0.00],
                ['id' => 'v3', 'name' => '700ml', 'price' => 8.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Banana', 'price' => 2.00],
                ['id' => 'o2', 'name' => 'Morango', 'price' => 3.00],
                ['id' => 'o3', 'name' => 'Granola', 'price' => 3.00],
                ['id' => 'o4', 'name' => 'Leite Ninho', 'price' => 5.00],
            ],
        ];

        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(201);

        $product = Product::where('name', 'Açaí Premium')->first();
        $this->assertCount(3, $product->variations);
        $this->assertCount(4, $product->optionals);
    }

    #[Test]
    public function pode_criar_produto_sem_variacoes_e_opcionais()
    {
        $productData = [
            'name' => 'Produto Simples',
            'description' => 'Produto sem customizações',
            'price' => 10.00,
            'price_cost' => 5.00,
            'qtd_stock' => 20,
            'is_active' => true,
            'categories' => [$this->category->uuid],
        ];

        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(201);

        $product = Product::where('name', 'Produto Simples')->first();
        $this->assertEquals([], $product->variations);
        $this->assertEquals([], $product->optionals);
    }

    // ============================================
    // TESTES DE LEITURA (READ)
    // ============================================

    #[Test]
    public function pode_listar_produto_com_variacoes_e_opcionais()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
                ['id' => 'v2', 'name' => 'Grande', 'price' => 10.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Extra 1', 'price' => 3.00],
                ['id' => 'o2', 'name' => 'Extra 2', 'price' => 5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $response = $this->getJson("/api/product/{$product->identify}");

        $response->assertStatus(200)
                ->assertJsonPath('data.0.variations', [
                    ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5],
                    ['id' => 'v2', 'name' => 'Grande', 'price' => 10],
                ])
                ->assertJsonPath('data.0.optionals', [
                    ['id' => 'o1', 'name' => 'Extra 1', 'price' => 3],
                    ['id' => 'o2', 'name' => 'Extra 2', 'price' => 5],
                ]);
    }

    // ============================================
    // TESTES DE ATUALIZAÇÃO (UPDATE)
    // ============================================

    #[Test]
    public function pode_adicionar_variacoes_a_produto_existente()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'variations' => [],
        ]);
        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'new1', 'name' => 'Tamanho P', 'price' => -3.00],
                ['id' => 'new2', 'name' => 'Tamanho G', 'price' => 5.00],
            ],
        ];

        $response = $this->postJson("/api/product/{$product->id}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);

        $updatedProduct = Product::find($product->id);
        $this->assertCount(2, $updatedProduct->variations);
        $this->assertEquals('Tamanho P', $updatedProduct->variations[0]['name']);
    }

    #[Test]
    public function pode_editar_variacoes_existentes()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
                ['id' => 'v2', 'name' => 'Grande', 'price' => 10.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Extra Pequeno', 'price' => -8.00], // Editado
                ['id' => 'v2', 'name' => 'Extra Grande', 'price' => 15.00],  // Editado
            ],
        ];

        $response = $this->postJson("/api/product/{$product->id}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);

        $updatedProduct = Product::find($product->id);
        $this->assertEquals('Extra Pequeno', $updatedProduct->variations[0]['name']);
        $this->assertEquals(-8.00, $updatedProduct->variations[0]['price']);
        $this->assertEquals('Extra Grande', $updatedProduct->variations[1]['name']);
        $this->assertEquals(15.00, $updatedProduct->variations[1]['price']);
    }

    #[Test]
    public function pode_remover_variacoes()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
                ['id' => 'v2', 'name' => 'Grande', 'price' => 10.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'variations' => [], // Removendo todas
        ];

        $response = $this->postJson("/api/product/{$product->id}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);

        $updatedProduct = Product::find($product->id);
        $this->assertEmpty($updatedProduct->variations);
    }

    #[Test]
    public function pode_adicionar_opcionais_a_produto_existente()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'optionals' => [],
        ]);
        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'optionals' => [
                ['id' => 'new1', 'name' => 'Complemento 1', 'price' => 4.00],
                ['id' => 'new2', 'name' => 'Complemento 2', 'price' => 6.00],
            ],
        ];

        $response = $this->postJson("/api/product/{$product->id}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);

        $updatedProduct = Product::find($product->id);
        $this->assertCount(2, $updatedProduct->optionals);
        $this->assertEquals('Complemento 1', $updatedProduct->optionals[0]['name']);
    }

    #[Test]
    public function pode_editar_opcionais_existentes()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
                ['id' => 'o2', 'name' => 'Queijo', 'price' => 3.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon Premium', 'price' => 7.00],  // Editado
                ['id' => 'o2', 'name' => 'Queijo Especial', 'price' => 4.50], // Editado
            ],
        ];

        $response = $this->postJson("/api/product/{$product->id}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);

        $updatedProduct = Product::find($product->id);
        $this->assertEquals('Bacon Premium', $updatedProduct->optionals[0]['name']);
        $this->assertEquals(7.00, $updatedProduct->optionals[0]['price']);
        $this->assertEquals('Queijo Especial', $updatedProduct->optionals[1]['name']);
        $this->assertEquals(4.50, $updatedProduct->optionals[1]['price']);
    }

    #[Test]
    public function pode_remover_opcionais()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
                ['id' => 'o2', 'name' => 'Queijo', 'price' => 3.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'optionals' => [], // Removendo todos
        ];

        $response = $this->postJson("/api/product/{$product->id}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);

        $updatedProduct = Product::find($product->id);
        $this->assertEmpty($updatedProduct->optionals);
    }

    #[Test]
    public function pode_adicionar_remover_e_editar_simultaneamente()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
                ['id' => 'v2', 'name' => 'Grande', 'price' => 10.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno Editado', 'price' => -3.00], // Editado
                // v2 removido
                ['id' => 'v3', 'name' => 'Extra Grande', 'price' => 15.00],   // Adicionado
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon Premium', 'price' => 7.00],   // Editado
                ['id' => 'o2', 'name' => 'Queijo', 'price' => 3.00],          // Adicionado
            ],
        ];

        $response = $this->postJson("/api/product/{$product->id}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);

        $updatedProduct = Product::find($product->id);
        
        // Verificar variações
        $this->assertCount(2, $updatedProduct->variations);
        $this->assertEquals('Pequeno Editado', $updatedProduct->variations[0]['name']);
        $this->assertEquals('Extra Grande', $updatedProduct->variations[1]['name']);
        
        // Verificar opcionais
        $this->assertCount(2, $updatedProduct->optionals);
        $this->assertEquals('Bacon Premium', $updatedProduct->optionals[0]['name']);
        $this->assertEquals('Queijo', $updatedProduct->optionals[1]['name']);
    }

    // ============================================
    // TESTES DE EXCLUSÃO (DELETE)
    // ============================================

    #[Test]
    public function ao_excluir_produto_remove_variacoes_e_opcionais()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $productId = $product->id;
        $productUuid = $product->uuid ?? $product->identify;

        $response = $this->deleteJson("/api/product/{$productUuid}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('products', ['id' => $productId]);
    }

    // ============================================
    // TESTES DE VALIDAÇÃO
    // ============================================

    #[Test]
    public function valida_estrutura_de_variacoes_no_create()
    {
        $productData = [
            'name' => 'Produto Inválido',
            'description' => 'Teste',
            'price' => 10.00,
            'price_cost' => 5.00,
            'qtd_stock' => 10,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['name' => 'Sem ID', 'price' => 5.00], // Faltando 'id'
            ],
        ];

        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['variations']);
    }

    #[Test]
    public function valida_estrutura_de_opcionais_no_create()
    {
        $productData = [
            'name' => 'Produto Inválido',
            'description' => 'Teste',
            'price' => 10.00,
            'price_cost' => 5.00,
            'qtd_stock' => 10,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Sem Preço'], // Faltando 'price'
            ],
        ];

        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['optionals']);
    }

    // ============================================
    // TESTES DE EDGE CASES
    // ============================================

    #[Test]
    public function aceita_variacoes_com_precos_negativos()
    {
        $productData = [
            'name' => 'Produto com Desconto',
            'description' => 'Teste',
            'price' => 20.00,
            'price_cost' => 10.00,
            'qtd_stock' => 10,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -10.00],
            ],
        ];

        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(201);

        $product = Product::where('name', 'Produto com Desconto')->first();
        $this->assertEquals(-10.00, $product->variations[0]['price']);
    }

    #[Test]
    public function aceita_variacoes_com_preco_zero()
    {
        $productData = [
            'name' => 'Produto Padrão',
            'description' => 'Teste',
            'price' => 20.00,
            'price_cost' => 10.00,
            'qtd_stock' => 10,
            'is_active' => true,
            'categories' => [$this->category->uuid],
            'variations' => [
                ['id' => 'v1', 'name' => 'Médio', 'price' => 0.00],
            ],
        ];

        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(201);

        $product = Product::where('name', 'Produto Padrão')->first();
        $this->assertEquals(0.00, $product->variations[0]['price']);
    }

    #[Test]
    public function mantem_variacoes_e_opcionais_ao_atualizar_outros_campos()
    {
        $product = Product::factory()->for($this->tenant)->create([
            'name' => 'Nome Original',
            'price' => 10.00,
            'variations' => [
                ['id' => 'v1', 'name' => 'Variação Original', 'price' => 5.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Opcional Original', 'price' => 3.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => 'Nome Atualizado', // Mudando apenas o nome
            'description' => $product->description,
            'price' => 15.00, // Mudando o preço
            'price_cost' => $product->price_cost,
            'qtd_stock' => $product->qtd_stock,
            'is_active' => $product->is_active,
            'categories' => [$this->category->uuid],
            'variations' => $product->variations, // Mantendo
            'optionals' => $product->optionals,   // Mantendo
        ];

        $response = $this->postJson("/api/product/{$product->id}", $updateData + ['_method' => 'PUT']);

        $response->assertStatus(200);

        $updatedProduct = Product::find($product->id);
        $this->assertEquals('Nome Atualizado', $updatedProduct->name);
        $this->assertEquals(15.00, $updatedProduct->price);
        $this->assertEquals('Variação Original', $updatedProduct->variations[0]['name']);
        $this->assertEquals('Opcional Original', $updatedProduct->optionals[0]['name']);
    }
}

