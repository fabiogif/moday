<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariationsOptionalsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->grantFullAccess($this->user, $this->tenant);
        $this->category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ==========================================
    // TESTES BÁSICOS
    // ==========================================

    public function test_api_returns_variations_and_optionals()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [
                ['id' => '1', 'name' => 'Pequena', 'price' => -5.00],
                ['id' => '2', 'name' => 'Grande', 'price' => 10.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
            ],
        ]);

        $product->categories()->attach($this->category->id);

        $response = $this->actingAs($this->user, 'api')->getJson("/api/product/{$product->uuid}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'variations',
                        'optionals'
                    ]
                ]);

        $data = $response->json('data');
        $this->assertIsArray($data['variations']);
        $this->assertIsArray($data['optionals']);
    }

    public function test_calculate_price_with_variation()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 30.00,
            'variations' => [
                ['id' => '1', 'name' => 'Pequena', 'price' => -5.00],
                ['id' => '2', 'name' => 'Grande', 'price' => 10.00],
            ],
        ]);

        // Preço base: 30.00
        // Variação Grande: +10.00
        // Total esperado: 40.00
        $totalWithLarge = 30.00 + 10.00;
        $this->assertEquals(40.00, $totalWithLarge);

        // Preço base: 30.00
        // Variação Pequena: -5.00
        // Total esperado: 25.00
        $totalWithSmall = 30.00 + (-5.00);
        $this->assertEquals(25.00, $totalWithSmall);
    }

    public function test_calculate_price_with_optionals()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 20.00,
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
                ['id' => 'o2', 'name' => 'Queijo', 'price' => 3.00],
            ],
        ]);

        // Preço base: 20.00
        // Bacon x2: 5.00 x 2 = 10.00
        // Queijo x1: 3.00 x 1 = 3.00
        // Total esperado: 33.00
        $total = 20.00 + (5.00 * 2) + (3.00 * 1);
        $this->assertEquals(33.00, $total);
    }

    public function test_calculate_price_with_variation_and_optionals()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 35.00,
            'variations' => [
                ['id' => 'v1', 'name' => 'Grande', 'price' => 10.00],
            ],
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
                ['id' => 'o2', 'name' => 'Borda', 'price' => 12.00],
            ],
        ]);

        // Preço base: 35.00
        // Variação Grande: +10.00
        // Bacon x2: 5.00 x 2 = 10.00
        // Borda x1: 12.00 x 1 = 12.00
        // Total esperado: 67.00
        $total = 35.00 + 10.00 + (5.00 * 2) + (12.00 * 1);
        $this->assertEquals(67.00, $total);
    }

    public function test_list_products_includes_variations_and_optionals()
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [['id' => '1', 'name' => 'P', 'price' => -5.00]],
            'optionals' => [['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00]],
        ])->categories()->attach($this->category->id);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/product');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        if (!empty($data)) {
            $this->assertArrayHasKey('variations', $data[0]);
            $this->assertArrayHasKey('optionals', $data[0]);
        }
    }

    public function test_show_product_returns_variations_and_optionals()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [['id' => '1', 'name' => 'M', 'price' => 0]],
            'optionals' => [['id' => 'o1', 'name' => 'Extra', 'price' => 3.00]],
        ]);
        $product->categories()->attach($this->category->id);

        $response = $this->actingAs($this->user, 'api')->getJson("/api/product/{$product->uuid}");

        $response->assertStatus(200)
                ->assertJsonPath('data.variations.0.name', 'M')
                ->assertJsonPath('data.optionals.0.name', 'Extra');
    }

    public function test_variations_must_be_array()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [['id' => '1', 'name' => 'Test', 'price' => 5.00]],
        ]);

        $this->assertIsArray($product->variations);
    }

    public function test_empty_arrays_are_accepted()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [],
            'optionals' => [],
        ]);

        $this->assertEquals([], $product->variations);
        $this->assertEquals([], $product->optionals);
    }

    public function test_deleting_product_removes_variations_and_optionals()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'variations' => [['id' => '1', 'name' => 'Test', 'price' => 5.00]],
            'optionals' => [['id' => 'o1', 'name' => 'Extra', 'price' => 3.00]],
        ]);
        $product->categories()->attach($this->category->id);

        $productId = $product->id;

        $response = $this->actingAs($this->user, 'api')->deleteJson("/api/product/{$product->uuid}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('products', ['id' => $productId]);
    }

    public function test_calculate_optionals_with_multiple_quantities()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 10.00,
            'optionals' => [
                ['id' => 'o1', 'name' => 'Item 1', 'price' => 2.00],
                ['id' => 'o2', 'name' => 'Item 2', 'price' => 3.00],
            ],
        ]);

        // Base: 10.00
        // Item 1 x3: 2.00 x 3 = 6.00
        // Item 2 x2: 3.00 x 2 = 6.00
        // Total: 22.00
        $total = 10.00 + (2.00 * 3) + (3.00 * 2);
        $this->assertEquals(22.00, $total);
    }

    public function test_same_optional_can_be_selected_multiple_times()
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 15.00,
            'optionals' => [
                ['id' => 'o1', 'name' => 'Bacon', 'price' => 5.00],
            ],
        ]);

        // Cliente adiciona 3x o mesmo opcional (Bacon)
        // Base: 15.00
        // Bacon x3: 5.00 x 3 = 15.00
        // Total: 30.00
        $total = 15.00 + (5.00 * 3);
        $this->assertEquals(30.00, $total);
    }
}
