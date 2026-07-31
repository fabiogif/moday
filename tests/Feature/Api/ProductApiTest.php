<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        // Criar plan primeiro
        $plan = \App\Models\Plan::factory()->create();
        
        // Criar tenant
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $plan->id
        ]);
        
        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        $this->grantFullAccess($this->user, $this->tenant);

        // Criar categoria
        $this->category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Eletrônicos'
        ]);
        
        // Gerar token JWT
        $this->token = JWTAuth::fromUser($this->user);
    }

    #[Test]
    public function pode_listar_produtos_do_tenant()
    {
        // Limpar produtos existentes do tenant antes do teste
        Product::where('tenant_id', $this->tenant->id)->forceDelete();
        
        // Criar produtos para o tenant
        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        // Criar produtos de outro tenant (não devem aparecer)
        $plan = \App\Models\Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        Product::factory()->count(2)->create([
            'tenant_id' => $otherTenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/product');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'identify',
                        'name',
                        'price',
                        'description',
                        'qtd_stock',
                        'is_active',
                        'categories'
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data'); // Agora deve retornar exatamente 3
        
        // Verificar que nenhum produto de outro tenant foi retornado
        $data = $response->json('data');
        foreach ($data as $product) {
            $this->assertDatabaseHas('products', [
                'id' => $product['id'],
                'tenant_id' => $this->tenant->id
            ]);
        }
    }

    #[Test]
    public function catalog_lista_apenas_produtos_ativos_com_estoque(): void
    {
        Product::where('tenant_id', $this->tenant->id)->forceDelete();

        $visible = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 5,
            'name' => 'Visível',
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
            'qtd_stock' => 10,
            'name' => 'Inativo',
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 0,
            'name' => 'Sem estoque',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product/catalog');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.identify', (string) $visible->uuid);

        $allProducts = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product');

        $allProducts->assertStatus(200)->assertJsonCount(3, 'data');
    }

    #[Test]
    public function limita_listagem_de_produtos_sem_paginacao(): void
    {
        config()->set('api.listing.unpaginated_cap', 2);

        Product::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.per_page', 2);
    }

    #[Test]
    public function limita_listagem_do_catalogo_sem_paginacao(): void
    {
        config()->set('api.listing.unpaginated_cap', 2);

        Product::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 10,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product/catalog')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    #[Test]
    public function pode_criar_produto_com_sucesso()
    {
        $image = UploadedFile::fake()->image('product.jpg', 800, 600);

        $productData = [
            'name' => 'iPhone 14 Pro',
            'description' => 'Smartphone Apple de última geração',
            'price' => 7999.90,
            'price_cost' => 6000.00,
            'promotional_price' => 7499.90,
            'qtd_stock' => 15,
            'brand' => 'Apple',
            'sku' => 'IPHONE-14-PRO-256',
            'weight' => 0.206,
            'is_active' => true,
            'image' => $image,
            'categories' => [$this->category->uuid] // Usar UUID ao invés de ID
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'identify',
                    'name',
                    'price',
                    'description',
                    'url',
                    'qtd_stock'
                ]
            ])
            ->assertJson([
                'message' => 'Produto criado com sucesso'
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'iPhone 14 Pro',
            'tenant_id' => $this->tenant->id,
            'price' => 7999.90,
            'qtd_stock' => 15
        ]);
    }

    #[Test]
    public function pode_atualizar_produto_existente()
    {
        // Criar produto inicial
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Original',
            'price' => 100.00,
            'qtd_stock' => 10
        ]);

        $product->categories()->attach($this->category->id);

        $updateData = [
            'name' => 'Produto Atualizado',
            'description' => 'Descrição atualizada do produto',
            'price' => 150.00,
            'price_cost' => 80.00,
            'promotional_price' => 120.00,
            'qtd_stock' => 25,
            'brand' => 'Nova Marca',
            'sku' => 'SKU-UPDATED',
            'is_active' => true,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson("/api/product/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'price',
                    'qtd_stock'
                ]
            ])
            ->assertJson([
                'message' => 'Produto atualizado com sucesso'
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto Atualizado',
            'price' => 150.00,
            'qtd_stock' => 25
        ]);
    }

    #[Test]
    public function pode_atualizar_produto_com_tenant_em_trial_ativo(): void
    {
        $plan = \App\Models\Plan::factory()->create(['price' => 99]);
        $tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'account_status' => 'trial',
            'trial_started_at' => now()->subDay(),
            'trial_expires_at' => now()->addDays(7),
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->grantFullAccess($user, $tenant);
        $token = JWTAuth::fromUser($user);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Produto Trial',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->putJson("/api/product/{$product->id}", [
            'name' => 'Produto Trial Atualizado',
            'description' => 'Descrição atualizada do produto',
            'price' => 8.9,
            'price_cost' => 4.5,
            'qtd_stock' => 200,
            'is_active' => true,
            'brand' => 'Genérico',
            'sku' => 'MED-DIP-500-20',
            'variations' => [],
            'optionals' => [],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Produto Trial Atualizado');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto Trial Atualizado',
        ]);
    }

    #[Test]
    public function pode_atualizar_produto_com_nova_imagem()
    {
        $oldImage = UploadedFile::fake()->image('old.jpg');
        
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto com Imagem',
            'image' => 'products/' . $oldImage->hashName()
        ]);

        Storage::disk('public')->put('products/' . $oldImage->hashName(), 'old content');

        $newImage = UploadedFile::fake()->image('new.jpg');

        $updateData = [
            'name' => 'Produto com Nova Imagem',
            'description' => $product->description ?? 'Descrição do produto',
            'price' => 200.00,
            'qtd_stock' => 5,
            'image' => $newImage,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson("/api/product/{$product->id}", $updateData);

        $response->assertStatus(200);
    }

    #[Test]
    public function nao_pode_atualizar_produto_de_outro_tenant()
    {
        $plan = \App\Models\Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $product = Product::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Produto de Outro Tenant'
        ]);

        $updateData = [
            'name' => 'Tentativa de Atualização',
            'description' => 'Descrição teste',
            'price' => 999.00,
            'qtd_stock' => 100,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson("/api/product/{$product->id}", $updateData);

        $response->assertStatus(404);

        // Verificar que o produto não foi alterado
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto de Outro Tenant',
            'tenant_id' => $otherTenant->id
        ]);
    }

    #[Test]
    public function validacao_falha_ao_criar_produto_sem_campos_obrigatorios()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'qtd_stock']);
    }

    #[Test]
    public function validacao_falha_ao_atualizar_produto_com_preco_invalido()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $updateData = [
            'name' => 'Produto Teste',
            'price' => -50.00, // Preço negativo inválido
            'qtd_stock' => 10
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson("/api/product/{$product->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    #[Test]
    public function pode_deletar_produto()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto a Deletar'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/product/{$product->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('products', [
            'id' => $product->id
        ]);
    }

    #[Test]
    public function nao_pode_deletar_produto_de_outro_tenant()
    {
        $plan = \App\Models\Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $product = Product::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/product/{$product->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null
        ]);
    }

    #[Test]
    public function pode_buscar_produto_por_id()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Específico'
        ]);

        $product->categories()->attach($this->category->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson("/api/product/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'identify',
                    'name',
                    'price',
                    'description',
                    'qtd_stock',
                    'categories'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => 'Produto Específico'
                ]
            ]);
    }

    #[Test]
    public function retorna_erro_ao_buscar_produto_inexistente()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/product/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function pode_obter_estatisticas_de_produtos()
    {
        Product::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 10
        ]);

        Product::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
            'qtd_stock' => 0
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/product/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'active',
                    'inactive',
                    'out_of_stock'
                ]
            ]);
    }

    #[Test]
    public function nome_do_produto_deve_ser_unico_por_tenant()
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Único'
        ]);

        $productData = [
            'name' => 'Produto Único',
            'price' => 100.00,
            'qtd_stock' => 10,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function pode_reutilizar_nome_e_barcode_apos_soft_delete(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Reciclável',
            'barcode' => '7891234567890',
            'description' => 'Descrição original',
            'price' => 50.00,
            'qtd_stock' => 5,
        ]);

        $product->delete();
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/product', [
            'name' => 'Produto Reciclável',
            'description' => 'Novo cadastro após exclusão',
            'price' => 60.00,
            'qtd_stock' => 8,
            'barcode' => '7891234567890',
            'categories' => [$this->category->uuid],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Produto Reciclável')
            ->assertJsonPath('data.barcode', '7891234567890');
    }

    #[Test]
    public function exclusao_via_api_libera_barcode_para_novo_cadastro(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto API Delete',
            'barcode' => '7891000000015',
            'description' => 'Será excluído via API',
            'price' => 10.00,
            'qtd_stock' => 1,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/product/{$product->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'barcode' => null,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/product', [
            'name' => 'Produto API Delete',
            'description' => 'Recadastrado após exclusão via API',
            'price' => 12.00,
            'qtd_stock' => 2,
            'barcode' => '7891000000015',
            'categories' => [$this->category->uuid],
        ])->assertStatus(201)
            ->assertJsonPath('data.barcode', '7891000000015');
    }

    #[Test]
    public function pode_atualizar_produto_mantendo_mesmo_nome()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Teste',
            'price' => 100.00
        ]);

        $updateData = [
            'name' => 'Produto Teste', // Mesmo nome
            'description' => $product->description ?? 'Descrição do produto',
            'price' => 150.00,
            'qtd_stock' => 20,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson("/api/product/{$product->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto Teste',
            'price' => 150.00
        ]);
    }

    #[Test]
    public function requer_autenticacao_para_acessar_produtos()
    {
        $response = $this->getJson('/api/product');
        $response->assertStatus(401);
    }

    #[Test]
    public function pode_atualizar_categorias_do_produto()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $category1 = Category::factory()->create(['tenant_id' => $this->tenant->id]);
        $category2 = Category::factory()->create(['tenant_id' => $this->tenant->id]);

        $product->categories()->attach($category1->id);

        $updateData = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'qtd_stock' => $product->qtd_stock,
            'categories' => [$category1->uuid, $category2->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->putJson("/api/product/{$product->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $category1->id
        ]);

        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $category2->id
        ]);
    }

    #[Test]
    public function pode_buscar_produto_por_codigo_de_barras(): void
    {
        Product::where('tenant_id', $this->tenant->id)->forceDelete();

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 10,
            'barcode' => '7891234567890',
            'sku' => 'SKU-TEST-001',
            'name' => 'Produto com barcode',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product/by-code/7891234567890');

        $response->assertStatus(200)
            ->assertJsonPath('data.identify', (string) $product->uuid)
            ->assertJsonPath('data.barcode', '7891234567890');
    }

    #[Test]
    public function busca_por_codigo_encontra_produto_pelo_sku(): void
    {
        Product::where('tenant_id', $this->tenant->id)->forceDelete();

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 5,
            'sku' => 'SKU-SCAN-99',
            'name' => 'Produto por SKU',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product/by-code/SKU-SCAN-99');

        $response->assertStatus(200)
            ->assertJsonPath('data.identify', (string) $product->uuid);
    }

    #[Test]
    public function busca_por_codigo_retorna_404_quando_nao_encontrado(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product/by-code/0000000000000');

        $response->assertStatus(404);
    }

    #[Test]
    public function pagina_produtos_quando_page_params_estao_presentes(): void
    {
        Product::where('tenant_id', $this->tenant->id)->forceDelete();
        Product::factory()->count(15)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product?page=1&per_page=10')
            ->assertStatus(200)
            ->assertJsonCount(10, 'data');

        $this->assertSame(1, $response->json('meta.current_page'));
        $this->assertSame(2, $response->json('meta.last_page'));
        $this->assertSame(15, $response->json('meta.total'));

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product?page=2&per_page=10')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function filtra_produtos_por_busca_quando_paginado(): void
    {
        Product::where('tenant_id', $this->tenant->id)->forceDelete();
        Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Furadeira Industrial']);
        Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Parafusadeira Elétrica']);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product?page=1&per_page=10&search=Furadeira')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Furadeira Industrial');
    }

    #[Test]
    public function pagina_catalogo_de_produtos_quando_page_params_estao_presentes(): void
    {
        Product::where('tenant_id', $this->tenant->id)->forceDelete();
        Product::factory()->count(12)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 10,
        ]);
        // Fora do catálogo (inativo) — não deve contar no total paginado.
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
            'qtd_stock' => 10,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/product/catalog?page=1&per_page=10')
            ->assertStatus(200)
            ->assertJsonCount(10, 'data');

        $this->assertSame(12, $response->json('meta.total'));
    }
}
