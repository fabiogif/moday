<?php

namespace Tests\Feature\SalesPerformance;

use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SalesPerformanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private string $token;
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->create([
            'plan_id' => $plan->id
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $this->paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Crédito',
            'is_active' => true
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    #[Test]
    public function pode_obter_dados_de_desempenho_de_vendas()
    {
        // Criar pedidos nos últimos 7 dias
        Order::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'total' => 100.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        // Criar novos clientes
        Client::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/sales-performance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'indicators' => [
                        'total_sales',
                        'total_sales_value',
                        'average_ticket',
                        'new_clients'
                    ],
                    'sales_by_hour',
                    'best_hour',
                    'sales_by_day',
                    'best_day',
                    'sales_by_payment_method'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    #[Test]
    public function pode_obter_dados_com_periodo_personalizado()
    {
        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(10)
        ]);

        $startDate = Carbon::now()->subDays(14)->format('Y-m-d');
        $endDate = Carbon::now()->subDays(7)->format('Y-m-d');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson("/api/sales-performance?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'indicators'
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    #[Test]
    public function pode_obter_dados_com_numero_de_dias_personalizado()
    {
        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/sales-performance?days=30');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ]);

        $this->assertTrue($response->json('success'));
    }

    #[Test]
    public function pode_exportar_dados_de_desempenho()
    {
        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/sales-performance/export');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'exported_at',
                    'tenant_id',
                    'period',
                    'indicators'
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->tenant->id, $response->json('data.tenant_id'));
    }

    #[Test]
    public function pode_atualizar_dados_de_desempenho()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->postJson('/api/sales-performance/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);

        $this->assertTrue($response->json('success'));
    }

    #[Test]
    public function requer_autenticacao_para_acessar_dados()
    {
        $response = $this->getJson('/api/sales-performance');
        $response->assertStatus(401);

        $response = $this->getJson('/api/sales-performance/export');
        $response->assertStatus(401);

        $response = $this->postJson('/api/sales-performance/refresh');
        $response->assertStatus(401);
    }

    #[Test]
    public function valida_periodo_quando_fornecido()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/sales-performance?start_date=2024-01-01&end_date=2023-12-01');

        $response->assertStatus(422);
    }

    #[Test]
    public function valida_numero_de_dias()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/sales-performance?days=500');

        $response->assertStatus(422);
    }

    #[Test]
    public function retorna_dados_filtrados_por_tenant()
    {
        // Criar pedidos do tenant atual
        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        // Criar pedidos de outro tenant
        $plan = Plan::factory()->create();
        $otherTenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $otherPaymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Pix',
            'is_active' => true
        ]);

        Order::factory()->count(10)->create([
            'tenant_id' => $otherTenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $otherPaymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/sales-performance');

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.indicators.total_sales.current'));
    }

    #[Test]
    public function calcula_crescimento_percentual_corretamente()
    {
        // Criar pedidos no período anterior
        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'total' => 100.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(10)
        ]);

        // Criar pedidos no período atual
        Order::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'total' => 100.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->getJson('/api/sales-performance');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verificar crescimento
        $this->assertArrayHasKey('growth', $data['indicators']['total_sales']);
        $this->assertArrayHasKey('growth', $data['indicators']['total_sales_value']);
        $this->assertArrayHasKey('growth', $data['indicators']['average_ticket']);
        $this->assertArrayHasKey('growth', $data['indicators']['new_clients']);

        // Verificar que o crescimento é positivo quando há mais vendas
        $this->assertGreaterThan(0, $data['indicators']['total_sales']['growth']);
    }
}

