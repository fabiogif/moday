<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\Table;
use App\Models\Category;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Classe base para testes de relatórios
 */
abstract class ReportTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected string $token;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar plano com acesso a relatórios
        $plan = Plan::factory()->create(['has_reports' => true]);
        
        // Criar tenant associado ao plano (accessible() passa trial.check)
        $this->tenant = Tenant::factory()->accessible()->create([
            'plan_id' => $plan->id,
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
        ]);
        
        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        
        // Gerar token JWT e autenticar
        $this->token = JWTAuth::fromUser($this->user);
        $this->actingAs($this->user);
    }

    /**
     * Cria dados de teste para relatórios
     */
    protected function createTestData(): void
    {
        // Criar categorias
        $category = Category::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pizzas',
        ]);
        
        // Criar clientes
        $clients = Client::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Criar produtos
        $products = Product::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Associar produtos a categorias
        foreach ($products as $product) {
            $product->categories()->attach($category->id);
        }
        
        // Criar pedidos sem mesas (simplified para o teste funcionar)
        foreach ($clients as $client) {
            // Pedidos presenciais (3 por cliente)
            for ($i = 0; $i < 3; $i++) {
                $order = Order::factory()->create([
                    'tenant_id' => $this->tenant->id,
                    'client_id' => $client->id,
                    'table_id' => null, // Sem mesa para simplicar
                    'is_delivery' => false,
                    'status' => 'Entregue',
                    'total' => rand(50, 200),
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
                
                // Adicionar produtos ao pedido com dados completos
                $selectedProducts = $products->random(rand(1, 3));
                foreach ($selectedProducts as $product) {
                    $order->products()->attach($product->id, [
                        'qty' => rand(1, 5),
                        'price' => $product->price ?? rand(10, 50)
                    ]);
                }
            }
            
            // Pedidos delivery (1 por cliente)
            $order = Order::factory()->create([
                'tenant_id' => $this->tenant->id,
                'client_id' => $client->id,
                'table_id' => null,
                'is_delivery' => true,
                'status' => 'Entregue',
                'total' => rand(50, 150),
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
            
            // Adicionar produtos ao pedido delivery
            $selectedProducts = $products->random(rand(1, 3));
            foreach ($selectedProducts as $product) {
                $order->products()->attach($product->id, [
                    'qty' => rand(1, 5),
                    'price' => $product->price ?? rand(10, 50)
                ]);
            }
        }
    }
    
    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }
}

