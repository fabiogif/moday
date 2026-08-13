<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = \App\Models\Tenant::first();

        $catMap = Category::where('tenant_id', $tenant->id)
            ->pluck('id', 'name');

        $products = [
            [
                'name' => 'Dipirona Sódica 500mg',
                'description' => 'Analgésico e antitérmico caixa com 20 comprimidos',
                'price' => 8.90, 'price_cost' => 4.50,
                'sku' => 'MED-DIP-500-20', 'brand' => 'Genérico',
                'unit_of_measure' => 'cx', 'units_per_box' => 12,
                'min_stock' => 50, 'reorder_point' => 30,
                'qtd_stock' => 200, 'product_type' => 'medicamento',
                'category' => 'Medicamentos',
            ],
            [
                'name' => 'Amoxicilina 500mg',
                'description' => 'Antibiótico caixa com 21 cápsulas',
                'price' => 24.50, 'price_cost' => 12.00,
                'sku' => 'MED-AMO-500-21', 'brand' => 'Genérico',
                'unit_of_measure' => 'cx', 'units_per_box' => 10,
                'min_stock' => 30, 'reorder_point' => 20,
                'qtd_stock' => 150, 'product_type' => 'medicamento',
                'requires_prescription' => true,
                'category' => 'Medicamentos',
            ],
            [
                'name' => 'Whey Protein 900g',
                'description' => 'Suplemento proteico sabor baunilha',
                'price' => 89.90, 'price_cost' => 55.00,
                'sku' => 'SUP-WHE-900-BAN', 'brand' => 'MaxForce',
                'unit_of_measure' => 'un', 'units_per_box' => 6,
                'min_stock' => 20, 'reorder_point' => 10,
                'qtd_stock' => 80, 'product_type' => 'suplemento',
                'category' => 'Suplementos',
            ],
            [
                'name' => 'Óleo de Cozinha Soja 900ml',
                'description' => 'Óleo vegetal de soja garrafa PET',
                'price' => 6.90, 'price_cost' => 4.20,
                'sku' => 'ALI-OLE-SOJ-900', 'brand' => 'Leve',
                'unit_of_measure' => 'un', 'units_per_box' => 12,
                'min_stock' => 100, 'reorder_point' => 60,
                'qtd_stock' => 500, 'product_type' => 'alimento',
                'category' => 'Alimentos',
            ],
            [
                'name' => 'Macarrão Espaguete 500g',
                'description' => 'Massa de trigo tipo 1 pacote 500g',
                'price' => 3.50, 'price_cost' => 1.90,
                'sku' => 'ALI-MAC-ESP-500', 'brand' => 'Vitallia',
                'unit_of_measure' => 'un', 'units_per_box' => 24,
                'min_stock' => 200, 'reorder_point' => 100,
                'qtd_stock' => 800, 'product_type' => 'alimento',
                'category' => 'Alimentos',
            ],
            [
                'name' => 'Água Mineral 500ml',
                'description' => 'Água mineral sem gás garrafa PET',
                'price' => 1.20, 'price_cost' => 0.60,
                'sku' => 'BEB-AGU-500', 'brand' => 'CristaLine',
                'unit_of_measure' => 'un', 'units_per_box' => 24,
                'min_stock' => 500, 'reorder_point' => 300,
                'qtd_stock' => 2000, 'product_type' => 'bebida',
                'category' => 'Bebidas',
            ],
            [
                'name' => 'Shampoo Anticaspa 400ml',
                'description' => 'Shampoo anticaspa uso diário',
                'price' => 14.90, 'price_cost' => 8.00,
                'sku' => 'HIG-SHA-400', 'brand' => 'CleanHead',
                'unit_of_measure' => 'un', 'units_per_box' => 12,
                'min_stock' => 50, 'reorder_point' => 30,
                'qtd_stock' => 200, 'product_type' => 'higiene',
                'category' => 'Higiene Pessoal',
            ],
            [
                'name' => 'Desinfetante Pinho 1L',
                'description' => 'Desinfetante aroma pinho embalagem 1 litro',
                'price' => 5.50, 'price_cost' => 2.80,
                'sku' => 'LIM-DES-PIN-1L', 'brand' => 'LimpMax',
                'unit_of_measure' => 'un', 'units_per_box' => 12,
                'min_stock' => 100, 'reorder_point' => 60,
                'qtd_stock' => 400, 'product_type' => 'limpeza',
                'category' => 'Limpeza',
            ],
        ];

        foreach ($products as $data) {
            $catName = $data['category'];
            unset($data['category']);

            $product = Product::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $data['name'],
                ],
                array_merge($data, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                    'flag'      => Str::kebab($data['name']),
                ])
            );

            if (isset($catMap[$catName])) {
                $product->categories()->syncWithoutDetaching([$catMap[$catName]]);
            }
        }

        $this->command->info('✅ Produtos de distribuidora criados.');
    }
}
