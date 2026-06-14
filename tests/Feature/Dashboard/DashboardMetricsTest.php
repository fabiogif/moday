<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar plan
        $plan = Plan::factory()->create();

        // Criar tenant
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $plan->id
        ]);

        // Criar usuário
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Gerar token JWT
        $this->token = JWTAuth::fromUser($this->user);
    }

    #[Test]
    public function pode_obter_metricas_de_pedidos_ultimos_7_dias()
    {
        // Criar pedidos nos últimos 7 dias
        for ($i = 0; $i < 7; $i++) {
            Order::factory()->count(rand(1, 5))->create([
                'tenant_id' => $this->tenant->id,
                'created_at' => now()->subDays($i),
                'status' => $i < 3 ? 'Entregue' : 'Em Preparo'
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/order');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta'
            ]);
        
        // Verificar que retorna dados
        $this->assertTrue($response->json('success'));
    }

    #[Test]
    public function pedidos_sao_filtrados_por_tenant()
    {
        // Criar pedidos do tenant atual
        Order::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Criar pedidos de outro tenant
        $plan = Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        Order::factory()->count(2)->create([
            'tenant_id' => $otherTenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/order');

        $response->assertStatus(200);
        
        // Verificar estrutura da resposta
        $this->assertArrayHasKey('success', $response->json());
        $this->assertArrayHasKey('data', $response->json());
        $this->assertTrue($response->json('success'));
    }

    #[Test]
    public function pode_obter_lista_de_clientes()
    {
        // Criar clientes nos últimos 6 meses
        for ($i = 0; $i < 6; $i++) {
            Client::factory()->count(rand(2, 5))->create([
                'tenant_id' => $this->tenant->id,
                'created_at' => now()->subMonths($i)
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/client');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'created_at'
                    ]
                ]
            ]);
        
        // Verificar que retorna clientes
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    #[Test]
    public function clientes_sao_filtrados_por_tenant()
    {
        // Criar clientes do tenant atual
        Client::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Criar clientes de outro tenant
        $plan = Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        Client::factory()->count(3)->create([
            'tenant_id' => $otherTenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/client');

        $response->assertStatus(200);
        
        // Verificar que todos os clientes são do tenant correto
        $clients = $response->json('data');
        foreach ($clients as $client) {
            $this->assertDatabaseHas('clients', [
                'id' => $client['id'],
                'tenant_id' => $this->tenant->id
            ]);
        }
    }

    #[Test]
    public function pedidos_tem_campo_status_valido()
    {
        // Criar pedidos com diferentes status
        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Em Preparo'
        ]);

        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/order');

        $response->assertStatus(200);
        
        $orders = $response->json('data');
        foreach ($orders as $order) {
            $this->assertNotEmpty($order['status']);
            $this->assertContains($order['status'], [
                'Pendente',
                'Em Preparo',
                'Pronto',
                'Em Entrega',
                'Entregue',
                'Cancelado'
            ]);
        }
    }

    #[Test]
    public function clientes_tem_campo_created_at_valido()
    {
        Client::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/client');

        $response->assertStatus(200);
        
        $clients = $response->json('data');
        foreach ($clients as $client) {
            $this->assertNotEmpty($client['created_at']);
            // Verificar que é uma data válida
            $this->assertNotFalse(strtotime($client['created_at']));
        }
    }

    #[Test]
    public function requer_autenticacao_para_acessar_metricas()
    {
        $response = $this->getJson('/api/order');
        $response->assertStatus(401);

        $response = $this->getJson('/api/client');
        $response->assertStatus(401);
    }
}

