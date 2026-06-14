<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOptionalsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant e usuário de teste
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_product_with_optionals()
    {
        $optionals = [
            ['id' => '1', 'name' => 'Grande', 'price' => 10.00],
            ['id' => '2', 'name' => 'Borda Recheada', 'price' => 8.00],
        ];

        $response = $this->actingAs($this->user, 'api')->postJson('/api/product', [
            'name' => 'Pizza Test',
            'description' => 'Pizza de teste',
            'price' => 30.00,
            'price_cost' => 15.00,
            'qtd_stock' => 10,
            'variations' => $optionals,
            'categories' => [],
        ]);

        $response->assertStatus(201);
        
        $product = Product::where('name', 'Pizza Test')->first();
        $this->assertNotNull($product);
        $this->assertCount(2, $product->variations);
        $this->assertEquals('Grande', $product->variations[0]['name']);
        $this->assertEquals(10.00, $product->variations[0]['price']);
    }

    public function test_can_update_product_optionals()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [
                ['id' => '1', 'name' => 'Pequeno', 'price' => 0],
            ]
        ]);

        $newOptionals = [
            ['id' => '1', 'name' => 'Pequeno', 'price' => 0],
            ['id' => '2', 'name' => 'Médio', 'price' => 5.00],
            ['id' => '3', 'name' => 'Grande', 'price' => 10.00],
        ];

        $response = $this->actingAs($this->user, 'api')->putJson("/api/product/{$product->id}", [
            'variations' => $newOptionals,
        ]);

        $response->assertStatus(200);
        
        $product->refresh();
        $this->assertCount(3, $product->variations);
    }

    public function test_optionals_are_returned_as_array()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [
                ['id' => '1', 'name' => 'Grande', 'price' => 10.00],
            ]
        ]);

        $response = $this->actingAs($this->user, 'api')->getJson("/api/product/{$product->uuid}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'variations' => [
                    '*' => ['id', 'name', 'price']
                ]
            ]
        ]);
    }

    public function test_can_remove_all_optionals()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [
                ['id' => '1', 'name' => 'Grande', 'price' => 10.00],
            ]
        ]);

        $response = $this->actingAs($this->user, 'api')->putJson("/api/product/{$product->id}", [
            'variations' => [],
        ]);

        $response->assertStatus(200);
        
        $product->refresh();
        $this->assertEmpty($product->variations);
    }

    public function test_optionals_with_zero_price()
    {
        $optionals = [
            ['id' => '1', 'name' => 'Tamanho Padrão', 'price' => 0],
            ['id' => '2', 'name' => 'Extra', 'price' => 5.00],
        ];

        $response = $this->actingAs($this->user, 'api')->postJson('/api/product', [
            'name' => 'Produto Teste',
            'description' => 'Teste',
            'price' => 20.00,
            'price_cost' => 10.00,
            'qtd_stock' => 10,
            'variations' => $optionals,
            'categories' => [],
        ]);

        $response->assertStatus(201);
        
        $product = Product::where('name', 'Produto Teste')->first();
        $this->assertEquals(0, $product->variations[0]['price']);
        $this->assertEquals(5.00, $product->variations[1]['price']);
    }

    public function test_null_variations_returns_empty_array()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => null,
        ]);

        $this->assertIsArray($product->variations);
        $this->assertEmpty($product->variations);
    }

    public function test_invalid_json_variations_returns_empty_array()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Forçar valor inválido diretamente no banco
        \DB::table('products')
            ->where('id', $product->id)
            ->update(['variations' => 'invalid-json']);

        $product->refresh();
        
        $this->assertIsArray($product->variations);
        $this->assertEmpty($product->variations);
    }

    public function test_calculate_total_price_with_optionals()
    {
        // Este é um teste de lógica, não de API
        $basePrice = 30.00;
        $optionals = [
            ['id' => '1', 'name' => 'Grande', 'price' => 10.00],
            ['id' => '2', 'name' => 'Borda', 'price' => 8.00],
        ];

        // Simular cálculo do cliente
        $selectedOptionals = ['1', '2'];
        $total = $basePrice;
        
        foreach ($optionals as $opt) {
            if (in_array($opt['id'], $selectedOptionals)) {
                $total += $opt['price'];
            }
        }

        $this->assertEquals(48.00, $total); // 30 + 10 + 8
    }
}

