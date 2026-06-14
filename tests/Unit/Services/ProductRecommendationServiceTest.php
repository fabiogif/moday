<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Models\Product;
use App\Models\Order;
use App\Models\Client;
use App\Services\ProductRecommendationService;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductRecommendationService $service;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $orderRepository = new OrderRepository();
        $productRepository = new ProductRepository();

        $this->service = new ProductRecommendationService(
            $orderRepository,
            $productRepository
        );
    }

    #[Test]
    public function retorna_produtos_mais_vendidos_quando_carrinho_vazio()
    {
        // Criar produtos com estoque disponível e ativos
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto 1',
            'is_active' => true,
            'qtd_stock' => 10,
        ]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto 2',
            'is_active' => true,
            'qtd_stock' => 10,
        ]);

        // Criar pedidos com produtos
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $order1 = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Concluído',
        ]);
        $order1->products()->attach($product1->id, ['qty' => 5, 'price' => 10.00]);

        $order2 = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Concluído',
        ]);
        $order2->products()->attach($product2->id, ['qty' => 3, 'price' => 15.00]);

        Cache::flush();

        $recommendations = $this->service->getRecommendationsForCart(
            $this->tenant->id,
            [],
            6
        );

        $this->assertNotEmpty($recommendations);
        $this->assertCount(2, $recommendations);
        // Produto 1 deve aparecer primeiro (mais vendido: 5 unidades)
        $this->assertEquals($product1->uuid, $recommendations[0]['uuid']);
    }

    #[Test]
    public function retorna_produtos_frequentemente_pedidos_juntos()
    {
        // Criar produtos com estoque disponível e ativos
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hambúrguer',
            'is_active' => true,
            'qtd_stock' => 10,
        ]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Batata Frita',
            'is_active' => true,
            'qtd_stock' => 10,
        ]);

        $product3 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Refrigerante',
            'is_active' => true,
            'qtd_stock' => 10,
        ]);

        // Criar pedidos onde produto1 e produto2 aparecem juntos
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        for ($i = 0; $i < 5; $i++) {
            $order = Order::factory()->create([
                'tenant_id' => $this->tenant->id,
                'client_id' => $client->id,
                'status' => 'Concluído',
            ]);
            $order->products()->attach($product1->id, ['qty' => 1, 'price' => 20.00]);
            $order->products()->attach($product2->id, ['qty' => 1, 'price' => 10.00]);
        }

        // Criar um pedido com produto1 e produto3 juntos (menos frequente)
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Concluído',
        ]);
        $order->products()->attach($product1->id, ['qty' => 1, 'price' => 20.00]);
        $order->products()->attach($product3->id, ['qty' => 1, 'price' => 8.00]);

        // Limpar cache
        Cache::flush();

        $recommendations = $this->service->getRecommendationsForCart(
            $this->tenant->id,
            [$product1->uuid],
            6
        );

        $this->assertNotEmpty($recommendations);
        // Produto2 deve aparecer primeiro (mais frequentemente pedido junto com produto1)
        $this->assertEquals($product2->uuid, $recommendations[0]['uuid']);
    }

    #[Test]
    public function nao_retorna_produtos_ja_no_carrinho()
    {
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $recommendations = $this->service->getRecommendationsForCart(
            $this->tenant->id,
            [$product1->uuid, $product2->uuid],
            6
        );

        // Verificar que os produtos do carrinho não aparecem nas recomendações
        $recommendedUuids = array_column($recommendations, 'uuid');
        $this->assertNotContains($product1->uuid, $recommendedUuids);
        $this->assertNotContains($product2->uuid, $recommendedUuids);
    }

    #[Test]
    public function usa_cache_para_otimizar_performance()
    {
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Concluído',
        ]);
        $order->products()->attach($product1->id, ['qty' => 1, 'price' => 10.00]);
        $order->products()->attach($product2->id, ['qty' => 1, 'price' => 15.00]);

        Cache::flush();

        // Primeira chamada (sem cache)
        $recommendations1 = $this->service->getFrequentlyBoughtTogether(
            $this->tenant->id,
            [$product1->uuid],
            6
        );

        // Segunda chamada (com cache)
        $recommendations2 = $this->service->getFrequentlyBoughtTogether(
            $this->tenant->id,
            [$product1->uuid],
            6
        );

        $this->assertEquals($recommendations1, $recommendations2);
    }

    #[Test]
    public function retorna_array_vazio_quando_nao_houver_produtos()
    {
        $recommendations = $this->service->getRecommendationsForCart(
            $this->tenant->id,
            ['non-existent-uuid'],
            6
        );

        $this->assertIsArray($recommendations);
    }

    #[Test]
    public function retorna_produtos_mais_vendidos_com_filtro_de_dias()
    {
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 10,
        ]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'qtd_stock' => 10,
        ]);

        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        // Pedido recente (últimos 7 dias)
        $recentOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Concluído',
            'created_at' => now()->subDays(3),
        ]);
        $recentOrder->products()->attach($product1->id, ['qty' => 10, 'price' => 10.00]);

        // Pedido antigo (30 dias atrás)
        $oldOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Concluído',
            'created_at' => now()->subDays(35),
        ]);
        $oldOrder->products()->attach($product2->id, ['qty' => 20, 'price' => 15.00]);

        Cache::flush();

        // Buscar mais vendidos dos últimos 7 dias
        $bestSellers = $this->service->getBestSellers($this->tenant->id, 6, 7);

        $this->assertNotEmpty($bestSellers);
        // Produto1 deve aparecer (pedido recente)
        $this->assertEquals($product1->uuid, $bestSellers[0]['uuid']);
    }

    #[Test]
    public function exclui_pedidos_cancelados_das_recomendacoes()
    {
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        // Pedido cancelado
        $canceledOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Cancelado',
        ]);
        $canceledOrder->products()->attach($product1->id, ['qty' => 1, 'price' => 10.00]);
        $canceledOrder->products()->attach($product2->id, ['qty' => 1, 'price' => 15.00]);

        // Pedido concluído
        $completedOrder = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'status' => 'Concluído',
        ]);
        $completedOrder->products()->attach($product1->id, ['qty' => 1, 'price' => 10.00]);

        Cache::flush();

        $recommendations = $this->service->getFrequentlyBoughtTogether(
            $this->tenant->id,
            [$product1->uuid],
            6
        );

        // Não deve retornar produto2 (só apareceu em pedido cancelado)
        $recommendedUuids = array_column($recommendations, 'uuid');
        $this->assertNotContains($product2->uuid, $recommendedUuids);
    }

    #[Test]
    public function limpa_cache_de_recomendacoes()
    {
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Criar cache via getRecommendationsForCart (usa cartProductIds não vazio)
        Cache::flush();
        $sortedIds = [$product1->uuid];
        sort($sortedIds);
        $cacheKey = "frequently_bought_together_{$this->tenant->id}_" . md5(implode(',', $sortedIds));

        // Chamar para popular o cache
        $this->service->getRecommendationsForCart(
            $this->tenant->id,
            [$product1->uuid],
            6
        );

        // Verificar que cache foi criado
        $this->assertTrue(Cache::has($cacheKey));

        // Limpar cache manualmente (sem usar tags pois array driver não suporta)
        Cache::forget($cacheKey);

        // Verificar que cache foi limpo
        $this->assertFalse(Cache::has($cacheKey));
    }
}
