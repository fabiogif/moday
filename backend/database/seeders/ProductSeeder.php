<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::all();
        }

        $products = [
            // Bebidas
            [
                'name' => 'Coca-Cola 350ml',
                'description' => 'Refrigerante Coca-Cola lata 350ml',
                'price' => 4.50,
                'price_cost' => 2.20,
                'promotional_price' => 3.99,
                'brand' => 'Coca-Cola',
                'sku' => 'COCA-350ML',
                'weight' => 0.350,
                'height' => 12.3,
                'width' => 5.3,
                'depth' => 5.3,
                'shipping_info' => 'Mantenha refrigerado',
                'warehouse_location' => 'A1-B1',
                'variations' => [
                    ['type' => 'Tamanho', 'value' => '350ml'],
                    ['type' => 'Tipo', 'value' => 'Original']
                ],
                'qtd_stock' => 100,
                'categories' => [$categories->where('name', 'Bebidas')->first()->id],
                'is_active' => true,
            ],
            [
                'name' => 'Suco de Laranja 300ml',
                'description' => 'Suco natural de laranja',
                'price' => 6.00,
                'price_cost' => 3.50,
                'brand' => 'Natural',
                'sku' => 'SUCO-LAR-300',
                'weight' => 0.300,
                'height' => 15.0,
                'width' => 6.0,
                'depth' => 6.0,
                'shipping_info' => 'Produto perecível - refrigerado',
                'warehouse_location' => 'A1-B2',
                'variations' => [
                    ['type' => 'Sabor', 'value' => 'Laranja'],
                    ['type' => 'Tipo', 'value' => 'Natural']
                ],
                'qtd_stock' => 50,
                'categories' => [$categories->where('name', 'Bebidas')->first()->id],
                'is_active' => true,
            ],
            // Produto básico sem campos opcionais
            [
                'name' => 'Água Mineral 500ml',
                'description' => 'Água mineral sem gás',
                'price' => 2.50,
                'price_cost' => 1.20,
                'qtd_stock' => 200,
                'categories' => [$categories->where('name', 'Bebidas')->first()->id],
                'is_active' => true,
            ],

            // Produto básico - sem logística nem variações
            [
                'name' => 'Pudim de Leite Simples',
                'description' => 'Pudim caseiro tradicional',
                'price' => 6.50,
                'price_cost' => 3.20,
                'qtd_stock' => 15,
                'categories' => [$categories->where('name', 'Sobremesas')->first()->id],
                'is_active' => true,
            ],

            // Pratos Principais
            [
                'name' => 'Frango Grelhado',
                'description' => 'Peito de frango grelhado com arroz e salada',
                'price' => 18.90,
                'price_cost' => 12.50,
                'promotional_price' => 16.90,
                'brand' => 'Chef House',
                'sku' => 'FRAN-GREL-001',
                'weight' => 0.450,
                'height' => 3.0,
                'width' => 25.0,
                'depth' => 20.0,
                'shipping_info' => 'Produto perecível - manter congelado',
                'warehouse_location' => 'B1-C1',
                'variations' => [
                    ['type' => 'Acompanhamento', 'value' => 'Arroz e Salada'],
                    ['type' => 'Preparo', 'value' => 'Grelhado']
                ],
                'qtd_stock' => 25,
                'categories' => [$categories->where('name', 'Pratos Principais')->first()->id],
                'is_active' => true,
            ],
            [
                'name' => 'Bife à Parmegiana',
                'description' => 'Bife empanado com molho de tomate e queijo',
                'price' => 22.50,
                'price_cost' => 15.80,
                'brand' => 'Chef House',
                'sku' => 'BIFE-PARM-001',
                'weight' => 0.380,
                'height' => 4.0,
                'width' => 20.0,
                'depth' => 15.0,
                'shipping_info' => 'Produto perecível - manter congelado',
                'warehouse_location' => 'B1-C2',
                'variations' => [
                    ['type' => 'Cobertura', 'value' => 'Queijo'],
                    ['type' => 'Molho', 'value' => 'Tomate']
                ],
                'qtd_stock' => 15,
                'categories' => [$categories->where('name', 'Pratos Principais')->first()->id],
                'is_active' => true,
            ],

            // Sobremesas
            [
                'name' => 'Pudim de Leite',
                'description' => 'Pudim caseiro com calda de caramelo',
                'price' => 8.50,
                'price_cost' => 4.20,
                'brand' => 'Doce Casa',
                'sku' => 'PUDIM-LEI-001',
                'weight' => 0.200,
                'height' => 6.0,
                'width' => 10.0,
                'depth' => 10.0,
                'shipping_info' => 'Manter refrigerado',
                'warehouse_location' => 'C1-D1',
                'variations' => [
                    ['type' => 'Sabor', 'value' => 'Leite'],
                    ['type' => 'Calda', 'value' => 'Caramelo']
                ],
                'qtd_stock' => 20,
                'categories' => [$categories->where('name', 'Sobremesas')->first()->id],
                'is_active' => true,
            ],

            // Produto inativo
            [
                'name' => 'Produto Descontinuado',
                'description' => 'Este produto foi descontinuado',
                'price' => 10.00,
                'price_cost' => 6.00,
                'brand' => 'Descontinuado',
                'sku' => 'DESC-001',
                'weight' => 0.100,
                'qtd_stock' => 0,
                'categories' => [$categories->first()->id],
                'is_active' => false,
            ],
        ];

        foreach ($products as $productData) {
            // Extrair variações e categorias
            $variations = $productData['variations'] ?? [];
            $categories = $productData['categories'] ?? [];
            unset($productData['variations'], $productData['categories']);

            $product = Product::create([
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'price_cost' => $productData['price_cost'] ?? null,
                'promotional_price' => $productData['promotional_price'] ?? null,
                'brand' => $productData['brand'] ?? null,
                'sku' => $productData['sku'] ?? null,
                'weight' => $productData['weight'] ?? null,
                'height' => $productData['height'] ?? null,
                'width' => $productData['width'] ?? null,
                'depth' => $productData['depth'] ?? null,
                'shipping_info' => $productData['shipping_info'] ?? null,
                'warehouse_location' => $productData['warehouse_location'] ?? null,
                'variations' => !empty($variations) ? json_encode($variations) : null,
                'qtd_stock' => $productData['qtd_stock'],
                'is_active' => $productData['is_active'],
                'uuid' => Str::uuid(),
                'flag' => Str::kebab($productData['name']),
                'tenant_id' => 1, // Assumindo que existe um tenant com ID 1
            ]);

            // attach categories via pivot
            if (!empty($categories)) {
                $attach = [];
                foreach ($categories as $categoryId) {
                    $attach[] = [
                        'product_id' => $product->id,
                        'category_id' => $categoryId,
                    ];
                }
                $product->categories()->attach($attach);
            }
        }
    }
}