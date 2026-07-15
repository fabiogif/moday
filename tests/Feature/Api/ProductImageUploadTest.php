<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Testes completos para upload de imagens em produtos
 * 
 * Cobre cenários de:
 * - Criação de produto com imagem
 * - Criação de produto sem imagem (opcional)
 * - Atualização de produto com nova imagem
 * - Atualização de produto sem alterar imagem
 * - Validação de tipos de arquivo
 * - Validação de tamanho de arquivo
 * - Armazenamento correto por tenant
 * - Remoção de imagem antiga ao atualizar
 */
class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;
    private Category $category;

    private function productDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('products');

        return $disk;
    }

    private function assertProductFileExists(string $path): void
    {
        $this->assertTrue(
            $this->productDisk()->exists($path),
            "Arquivo não encontrado no disco 'products': {$path}"
        );
    }

    private function assertProductFileMissing(string $path): void
    {
        $this->assertFalse(
            $this->productDisk()->exists($path),
            "Arquivo inesperado encontrado no disco 'products': {$path}"
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Usar fake storage para testes
        Storage::fake('public');
        Storage::fake('products');
        
        // Criar estrutura básica para os testes
        $plan = \App\Models\Plan::factory()->create();
        
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'uuid' => 'test-tenant-uuid'
        ]);
        
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $this->category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Eletrônicos'
        ]);
        
        $this->token = JWTAuth::fromUser($this->user);
    }

    #[Test]
    public function pode_criar_produto_com_imagem_jpg()
    {
        $image = UploadedFile::fake()->image('produto.jpg', 800, 600);

        $productData = [
            'name' => 'Notebook Dell',
            'description' => 'Notebook para uso profissional',
            'price' => 3500.00,
            'qtd_stock' => 10,
            'image' => $image,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Produto criado com sucesso'
            ]);

        // Verificar que a imagem foi salva no diretório correto do tenant
        $this->assertProductFileExists(
            "tenants/{$this->tenant->uuid}/products/{$image->hashName()}"
        );
    }

    #[Test]
    public function pode_criar_produto_com_imagem_png()
    {
        $image = UploadedFile::fake()->image('produto.png', 1024, 768);

        $productData = [
            'name' => 'Mouse Logitech',
            'description' => 'Mouse sem fio',
            'price' => 150.00,
            'qtd_stock' => 50,
            'image' => $image,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(201);
        
        $this->assertProductFileExists(
            "tenants/{$this->tenant->uuid}/products/{$image->hashName()}"
        );
    }

    #[Test]
    public function pode_criar_produto_sem_imagem()
    {
        $productData = [
            'name' => 'Produto Sem Imagem',
            'description' => 'Este produto não tem imagem',
            'price' => 99.90,
            'qtd_stock' => 25,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Produto criado com sucesso'
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Produto Sem Imagem',
            'tenant_id' => $this->tenant->id,
            'image' => null
        ]);
    }

    #[Test]
    public function valida_tamanho_maximo_da_imagem()
    {
        // Criar imagem maior que 2MB (limite configurado)
        $image = UploadedFile::fake()->create('produto.jpg', 3000); // 3MB

        $productData = [
            'name' => 'Produto Grande',
            'description' => 'Teste de imagem grande',
            'price' => 100.00,
            'qtd_stock' => 10,
            'image' => $image,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    #[Test]
    public function valida_tipo_de_arquivo_da_imagem()
    {
        // Criar arquivo que não é imagem
        $file = UploadedFile::fake()->create('documento.pdf', 1000);

        $productData = [
            'name' => 'Produto com PDF',
            'description' => 'Tentativa de upload de arquivo inválido',
            'price' => 100.00,
            'qtd_stock' => 10,
            'image' => $file,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    #[Test]
    public function pode_atualizar_produto_com_nova_imagem()
    {
        // Criar produto com imagem inicial
        $oldImage = UploadedFile::fake()->image('imagem-antiga.jpg', 600, 400);
        
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Original',
            'price' => 200.00,
            'image' => \App\Helpers\ImageHelper::generateImageUrl("tenants/{$this->tenant->uuid}/products/" . $oldImage->hashName(), 'products')
        ]);

        // Simular que a imagem antiga existe
        $this->productDisk()->put(
            "tenants/{$this->tenant->uuid}/products/{$oldImage->hashName()}",
            $oldImage->getContent()
        );

        // Nova imagem para atualização
        $newImage = UploadedFile::fake()->image('imagem-nova.jpg', 800, 600);

        $updateData = [
            '_method' => 'PUT',
            'name' => 'Produto Atualizado',
            'description' => 'Descrição atualizada',
            'price' => 250.00,
            'qtd_stock' => 15,
            'image' => $newImage,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson("/api/product/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Produto atualizado com sucesso'
            ]);

        // Verificar que a nova imagem foi salva
        $this->assertProductFileExists(
            "tenants/{$this->tenant->uuid}/products/{$newImage->hashName()}"
        );
    }

    #[Test]
    public function pode_atualizar_produto_sem_alterar_imagem()
    {
        // Criar produto com imagem
        $image = UploadedFile::fake()->image('imagem-original.jpg');
        
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto com Imagem',
            'price' => 300.00,
            'image' => \App\Helpers\ImageHelper::generateImageUrl("tenants/{$this->tenant->uuid}/products/" . $image->hashName(), 'products')
        ]);

        $this->productDisk()->put(
            "tenants/{$this->tenant->uuid}/products/{$image->hashName()}",
            $image->getContent()
        );

        $originalImageUrl = $product->image;

        // Atualizar produto SEM enviar nova imagem
        $updateData = [
            '_method' => 'PUT',
            'name' => 'Produto Atualizado Sem Nova Imagem',
            'description' => 'Apenas atualização de texto',
            'price' => 350.00,
            'qtd_stock' => 20,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson("/api/product/{$product->id}", $updateData);

        $response->assertStatus(200);

        // Verificar que a imagem original ainda existe
        $this->assertProductFileExists(
            "tenants/{$this->tenant->uuid}/products/{$image->hashName()}"
        );
    }

    #[Test]
    public function imagem_e_salva_no_diretorio_correto_do_tenant()
    {
        $image = UploadedFile::fake()->image('teste-tenant.jpg');

        $productData = [
            'name' => 'Produto Teste Tenant',
            'description' => 'Teste de armazenamento por tenant',
            'price' => 100.00,
            'qtd_stock' => 10,
            'image' => $image,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(201);

        // Verificar estrutura de diretórios correta
        $expectedPath = "tenants/{$this->tenant->uuid}/products/{$image->hashName()}";
        
        $this->assertProductFileExists($expectedPath);
        
        // Verificar que não foi salvo em outro lugar
        $this->assertProductFileMissing("products/{$image->hashName()}");
    }

    #[Test]
    public function url_da_imagem_e_salva_corretamente_no_banco()
    {
        $image = UploadedFile::fake()->image('produto-url.jpg');

        $productData = [
            'name' => 'Produto URL Teste',
            'description' => 'Teste de URL da imagem',
            'price' => 150.00,
            'qtd_stock' => 5,
            'image' => $image,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(201);

        // Verificar que a URL completa foi salva no banco
        $this->assertDatabaseHas('products', [
            'name' => 'Produto URL Teste',
            'tenant_id' => $this->tenant->id
        ]);

        $product = Product::where('name', 'Produto URL Teste')->first();

        // Imagem é persistida como path relativo no disco products
        $this->assertNotEmpty($product->image);
        $this->assertStringContainsString("tenants/{$this->tenant->uuid}/products/", $product->image);
        $this->assertStringEndsWith('.jpg', $product->image);
    }

    #[Test]
    public function pode_criar_produto_com_imagem_gif()
    {
        $image = UploadedFile::fake()->image('animacao.gif', 500, 500);

        $productData = [
            'name' => 'Produto com GIF',
            'description' => 'Teste de upload de GIF',
            'price' => 75.00,
            'qtd_stock' => 30,
            'image' => $image,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(201);
        
        $this->assertProductFileExists(
            "tenants/{$this->tenant->uuid}/products/{$image->hashName()}"
        );
    }

    #[Test]
    public function pode_criar_produto_com_imagem_svg()
    {
        // SVG é permitido na validação
        $image = UploadedFile::fake()->create('icone.svg', 50, 'image/svg+xml');

        $productData = [
            'name' => 'Produto com SVG',
            'description' => 'Teste de upload de SVG',
            'price' => 25.00,
            'qtd_stock' => 100,
            'image' => $image,
            'categories' => [$this->category->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData);

        $response->assertStatus(201);
    }

    #[Test]
    public function multiplos_produtos_podem_ter_imagens_com_mesmo_nome_original()
    {
        // Dois produtos diferentes com arquivos de mesmo nome
        $image1 = UploadedFile::fake()->image('produto.jpg', 600, 400);
        $image2 = UploadedFile::fake()->image('produto.jpg', 600, 400);

        // Criar primeiro produto
        $productData1 = [
            'name' => 'Produto 1',
            'description' => 'Primeiro produto',
            'price' => 100.00,
            'qtd_stock' => 10,
            'image' => $image1,
            'categories' => [$this->category->uuid]
        ];

        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData1);

        $response1->assertStatus(201);

        // Criar segundo produto com imagem de mesmo nome
        $productData2 = [
            'name' => 'Produto 2',
            'description' => 'Segundo produto',
            'price' => 200.00,
            'qtd_stock' => 20,
            'image' => $image2,
            'categories' => [$this->category->uuid]
        ];

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/product', $productData2);

        $response2->assertStatus(201);

        // Verificar que ambas as imagens foram salvas com nomes únicos (hash)
        $this->assertProductFileExists(
            "tenants/{$this->tenant->uuid}/products/{$image1->hashName()}"
        );
        
        $this->assertProductFileExists(
            "tenants/{$this->tenant->uuid}/products/{$image2->hashName()}"
        );

        // Os hashes devem ser diferentes mesmo com nome original igual
        $this->assertNotEquals($image1->hashName(), $image2->hashName());
    }

    #[Test]
    public function requer_autenticacao_para_upload_de_imagem()
    {
        $image = UploadedFile::fake()->image('sem-auth.jpg');

        $productData = [
            'name' => 'Produto Sem Auth',
            'description' => 'Teste sem autenticação',
            'price' => 100.00,
            'qtd_stock' => 10,
            'image' => $image,
            'categories' => [$this->category->uuid]
        ];

        // Requisição sem token de autenticação
        $response = $this->postJson('/api/product', $productData);

        $response->assertStatus(401);

        // Verificar que nenhuma imagem foi salva
        $this->assertProductFileMissing(
            "tenants/{$this->tenant->uuid}/products/{$image->hashName()}"
        );
    }

    #[Test]
    public function nao_pode_fazer_upload_para_produto_de_outro_tenant()
    {
        // Criar outro tenant com UUID único
        $plan = \App\Models\Plan::factory()->create();
        
        // Criar tenant sem observer para evitar produtos automáticos
        $otherTenant = Tenant::withoutEvents(function () use ($plan) {
            return Tenant::factory()->create([
                'plan_id' => $plan->id,
                'uuid' => 'other-tenant-uuid-' . uniqid()
            ]);
        });
        
        $otherCategory = Category::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Categoria Outro Tenant ' . uniqid()
        ]);

        // Produto do outro tenant
        $otherProduct = Product::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Produto Outro Tenant ' . uniqid(),
            'description' => 'Descrição teste',
            'price' => 100.00,
            'qtd_stock' => 10,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'flag' => \Illuminate\Support\Str::slug('Produto Outro Tenant')
        ]);

        $image = UploadedFile::fake()->image('hack.jpg');

        $updateData = [
            '_method' => 'PUT',
            'name' => 'Tentativa de Hack ' . uniqid(),
            'description' => 'Não deve funcionar',
            'price' => 999.00,
            'qtd_stock' => 100,
            'image' => $image,
            'categories' => [$otherCategory->uuid]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson("/api/product/{$otherProduct->id}", $updateData);

        $response->assertStatus(404);

        // Verificar que a imagem não foi salva em nenhum tenant
        $this->assertProductFileMissing(
            "tenants/{$this->tenant->uuid}/products/{$image->hashName()}"
        );
        
        $this->assertProductFileMissing(
            "tenants/{$otherTenant->uuid}/products/{$image->hashName()}"
        );
    }
}
