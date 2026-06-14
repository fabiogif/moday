<?php

namespace App\Observers;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\Category;
use App\Models\Product;
use App\Models\PaymentMethod;

class TenantObserver
{

    public function creating(Tenant $tenant): void
    {
        $tenant->uuid = Str::uuid();
        $tenant->url = Str::kebab($tenant->name);
        
        // Gerar slug baseado no nome se não fornecido
        if (empty($tenant->slug)) {
            $tenant->slug = Str::slug($tenant->name);
        }
    }

    /**
     * Handle the Tenant "created" event.
     * Cria categorias, produtos e formas de pagamento padrão após criação do tenant
     */
    public function created(Tenant $tenant): void
    {
        if (app()->environment('testing')) {
            return;
        }

        // Criar categorias exemplo
        $categories = [
            [
                'name' => 'Bebidas',
                'description' => 'Bebidas diversas',
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Lanches',
                'description' => 'Lanches e petiscos',
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Sobremesas',
                'description' => 'Doces e sobremesas',
                'tenant_id' => $tenant->id,
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $categoryData['name'],
                ],
                [
                    'description' => $categoryData['description'],
                    'status'      => 'A',
                ]
            );
            $createdCategories[$category->name] = $category;
        }

        // Criar produtos exemplo
        $products = [
            [
                'name' => 'Refrigerante Lata 350ml',
                'description' => 'Refrigerante em lata de 350ml',
                'price' => 5.00,
                'price_cost' => 3.00,
                'qtd_stock' => 50,
                'is_active' => true,
                'tenant_id' => $tenant->id,
                'category' => 'Bebidas',
            ],
            [
                'name' => 'Água Mineral 500ml',
                'description' => 'Água mineral sem gás',
                'price' => 3.00,
                'price_cost' => 1.50,
                'qtd_stock' => 100,
                'is_active' => true,
                'tenant_id' => $tenant->id,
                'category' => 'Bebidas',
            ],
            [
                'name' => 'X-Burger',
                'description' => 'Hambúrguer com queijo, alface e tomate',
                'price' => 18.00,
                'price_cost' => 8.00,
                'qtd_stock' => 30,
                'is_active' => true,
                'tenant_id' => $tenant->id,
                'category' => 'Lanches',
            ],
            [
                'name' => 'Batata Frita',
                'description' => 'Porção de batata frita crocante',
                'price' => 12.00,
                'price_cost' => 5.00,
                'qtd_stock' => 40,
                'is_active' => true,
                'tenant_id' => $tenant->id,
                'category' => 'Lanches',
            ],
            [
                'name' => 'Brownie',
                'description' => 'Brownie de chocolate com castanhas',
                'price' => 8.00,
                'price_cost' => 3.50,
                'qtd_stock' => 25,
                'is_active' => true,
                'tenant_id' => $tenant->id,
                'category' => 'Sobremesas',
            ],
        ];

        foreach ($products as $productData) {
            $categoryName = $productData['category'];
            unset($productData['category']);

            $product = Product::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $productData['name'],
                ],
                array_merge($productData, [
                    'flag' => Str::kebab($productData['name']),
                ])
            );

            // Associar categoria ao produto
            if (isset($createdCategories[$categoryName])) {
                $product->categories()->syncWithoutDetaching([$createdCategories[$categoryName]->id]);
            }
        }

        // Criar formas de pagamento padrão
        $paymentMethods = [
            [
                'name' => 'Dinheiro',
                'type' => 'money',
                'description' => 'Pagamento em dinheiro',
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ],
            [
                'name' => 'PIX',
                'type' => 'pix',
                'description' => 'Pagamento via PIX',
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ],
            [
                'name' => 'Cartão de Crédito',
                'type' => 'credit_card',
                'description' => 'Pagamento com cartão de crédito',
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ],
            [
                'name' => 'Cartão de Débito',
                'type' => 'debit_card',
                'description' => 'Pagamento com cartão de débito',
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ],
        ];

        foreach ($paymentMethods as $paymentMethodData) {
            $paymentMethod = PaymentMethod::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $paymentMethodData['name'],
                ],
                [
                    'type' => $paymentMethodData['type'] ?? null,
                    'description' => $paymentMethodData['description'] ?? $paymentMethodData['name'],
                    'is_active' => $paymentMethodData['is_active'] ?? true,
                ]
            );

            PaymentMethod::where('tenant_id', $tenant->id)
                ->where('name', $paymentMethodData['name'])
                ->where('id', '!=', $paymentMethod->id)
                ->delete();
        }
    }

    public function updating(Tenant $tenant): void
    {
        $tenant->url = Str::kebab($tenant->name);
        
        // Atualizar slug se o nome mudou e slug não foi fornecido manualmente
        if ($tenant->isDirty('name') && empty($tenant->slug)) {
            $tenant->slug = Str::slug($tenant->name);
        }
    }


}
