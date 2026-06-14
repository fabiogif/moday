<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testes Simples e Funcionais de Variações e Opcionais
 */
class ProductVariationsOptionalsSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->category = Category::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    #[Test]
    public function pode_criar_produto_direto_no_banco_com_variacoes()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
                ['id' => 'v2', 'name' => 'Grande', 'price' => 10.00],
            ],
        ]);

        $this->assertCount(2, $product->variations);
        $this->assertEquals('Pequeno', $product->variations[0]['name']);
        $this->assertEquals(-5.00, $product->variations[0]['price']);
    }

    #[Test]
    public function pode_criar_produto_direto_no_banco_com_opcionais()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
                ['id' => 'o2', 'name' => 'Queijo', 'price' => 3.00],
            ],
        ]);

        $this->assertCount(2, $product->optionals);
        $this->assertEquals('Bacon', $product->optionals[0]['name']);
        $this->assertEquals(5.00, $product->optionals[0]['price']);
    }

    #[Test]
    public function api_retorna_variacoes_como_array()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [
                ['id' => 'v1', 'name' => 'P', 'price' => -5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $response = $this->actingAs($this->user, 'api')
                        ->getJson("/api/product/{$product->uuid}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertIsArray($data['variations']);
        $this->assertNotEmpty($data['variations']);
    }

    #[Test]
    public function api_retorna_opcionais_como_array()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'optionals' => [
                ['id' => 'o1', 'name' => 'Extra', 'price' => 3.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $response = $this->actingAs($this->user, 'api')
                        ->getJson("/api/product/{$product->uuid}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertIsArray($data['optionals']);
        $this->assertNotEmpty($data['optionals']);
    }

    #[Test]
    public function pode_atualizar_variacoes_de_produto_existente()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $newVariations = [
            ['id' => 'v1', 'name' => 'Pequeno Editado', 'price' => -3.00],
            ['id' => 'v2', 'name' => 'Grande Novo', 'price' => 12.00],
        ];

        $response = $this->actingAs($this->user, 'api')
                        ->postJson("/api/product/{$product->uuid}", [
                            '_method' => 'PUT',
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'price_cost' => $product->price_cost,
                            'qtd_stock' => $product->qtd_stock,
                            'is_active' => true,
                            'variations' => json_encode($newVariations),
                            'categories' => [$this->category->uuid],
                        ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertCount(2, $product->variations);
        $this->assertEquals('Pequeno Editado', $product->variations[0]['name']);
    }

    #[Test]
    public function pode_atualizar_opcionais_de_produto_existente()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $newOptionals = [
            ['id' => 'o1', 'name' => 'Bacon Premium', 'price' => 7.00],
            ['id' => 'o2', 'name' => 'Queijo', 'price' => 3.00],
        ];

        $response = $this->actingAs($this->user, 'api')
                        ->postJson("/api/product/{$product->uuid}", [
                            '_method' => 'PUT',
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'price_cost' => $product->price_cost,
                            'qtd_stock' => $product->qtd_stock,
                            'is_active' => true,
                            'optionals' => json_encode($newOptionals),
                            'categories' => [$this->category->uuid],
                        ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertCount(2, $product->optionals);
        $this->assertEquals('Bacon Premium', $product->optionals[0]['name']);
    }

    #[Test]
    public function pode_remover_todas_variacoes()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [
                ['id' => 'v1', 'name' => 'Pequeno', 'price' => -5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $response = $this->actingAs($this->user, 'api')
                        ->postJson("/api/product/{$product->uuid}", [
                            '_method' => 'PUT',
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'price_cost' => $product->price_cost,
                            'qtd_stock' => $product->qtd_stock,
                            'is_active' => true,
                            'variations' => json_encode([]),
                            'categories' => [$this->category->uuid],
                        ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertEmpty($product->variations);
    }

    #[Test]
    public function pode_remover_todos_opcionais()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
            ],
        ]);
        $product->categories()->attach($this->category->id);

        $response = $this->actingAs($this->user, 'api')
                        ->postJson("/api/product/{$product->uuid}", [
                            '_method' => 'PUT',
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'price_cost' => $product->price_cost,
                            'qtd_stock' => $product->qtd_stock,
                            'is_active' => true,
                            'optionals' => json_encode([]),
                            'categories' => [$this->category->uuid],
                        ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertEmpty($product->optionals);
    }

    #[Test]
    public function variacoes_null_retornam_array_vazio()
    {
        $product = Product::create([
            'name' => 'Test',
            'description' => 'Test',
            'price' => 10,
            'price_cost' => 5,
            'qtd_stock' => 10,
            'uuid' => \Str::uuid(),
            'flag' => 'test',
            'tenant_id' => $this->tenant->id,
            'variations' => null,
        ]);

        $product->refresh();
        $this->assertIsArray($product->variations);
        $this->assertEmpty($product->variations);
    }

    #[Test]
    public function opcionais_null_retornam_array_vazio()
    {
        $product = Product::create([
            'name' => 'Test',
            'description' => 'Test',
            'price' => 10,
            'price_cost' => 5,
            'qtd_stock' => 10,
            'uuid' => \Str::uuid(),
            'flag' => 'test',
            'tenant_id' => $this->tenant->id,
            'optionals' => null,
        ]);

        $product->refresh();
        $this->assertIsArray($product->optionals);
        $this->assertEmpty($product->optionals);
    }
}

