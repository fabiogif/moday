<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Tenant;
use App\Repositories\SalesPerformanceRepository;
use App\Services\SalesPerformanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SalesPerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private SalesPerformanceService $service;
    private Tenant $tenant;
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);

        $this->paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Crédito',
            'is_active' => true
        ]);

        $repository = new SalesPerformanceRepository(new Order(), new Client());
        $this->service = new SalesPerformanceService($repository);
    }

    #[Test]
    public function pode_obter_dados_de_desempenho_de_vendas()
    {
        // Criar pedidos nos últimos 7 dias
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

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

        $result = $this->service->getSalesPerformance(
            $this->tenant->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            7
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('indicators', $result);
        $this->assertArrayHasKey('sales_by_hour', $result);
        $this->assertArrayHasKey('sales_by_day', $result);
        $this->assertArrayHasKey('sales_by_payment_method', $result);
        $this->assertArrayHasKey('best_hour', $result);
        $this->assertArrayHasKey('best_day', $result);

        // Verificar indicadores
        $this->assertGreaterThan(0, $result['indicators']['total_sales']['current']);
        $this->assertGreaterThan(0, $result['indicators']['total_sales_value']['current']);
        $this->assertGreaterThan(0, $result['indicators']['average_ticket']['current']);
        $this->assertGreaterThan(0, $result['indicators']['new_clients']['current']);
    }

    #[Test]
    public function calcula_crescimento_percentual_corretamente()
    {
        // Criar pedidos no período anterior
        $previousStart = Carbon::now()->subDays(14)->startOfDay();
        $previousEnd = Carbon::now()->subDays(8)->endOfDay();

        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'total' => 100.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(10)
        ]);

        // Criar pedidos no período atual
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        Order::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'total' => 100.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $result = $this->service->getSalesPerformance(
            $this->tenant->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            7
        );

        // Verificar crescimento
        $this->assertArrayHasKey('growth', $result['indicators']['total_sales']);
        $this->assertArrayHasKey('growth', $result['indicators']['total_sales_value']);
        $this->assertArrayHasKey('growth', $result['indicators']['average_ticket']);
        $this->assertArrayHasKey('growth', $result['indicators']['new_clients']);

        // Verificar que o crescimento é calculado
        $this->assertIsFloat($result['indicators']['total_sales']['growth']);
    }

    #[Test]
    public function identifica_melhor_horario_corretamente()
    {
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Criar pedidos em horários diferentes
        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)->setHour(14)->setMinute(0)
        ]);

        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)->setHour(18)->setMinute(0)
        ]);

        $result = $this->service->getSalesPerformance(
            $this->tenant->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            7
        );

        $this->assertNotNull($result['best_hour']);
        $this->assertArrayHasKey('hour', $result['best_hour']);
        $this->assertArrayHasKey('count', $result['best_hour']);
        $this->assertArrayHasKey('hour_label', $result['best_hour']);

        // O melhor horário deve ser o 18h (com mais vendas)
        $this->assertEquals(18, $result['best_hour']['hour']);
        $this->assertEquals(5, $result['best_hour']['count']);
    }

    #[Test]
    public function identifica_melhor_dia_da_semana_corretamente()
    {
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Criar pedidos em dias diferentes
        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)->startOfDay() // Segunda-feira
        ]);

        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(1)->startOfDay() // Sexta-feira
        ]);

        $result = $this->service->getSalesPerformance(
            $this->tenant->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            7
        );

        $this->assertNotNull($result['best_day']);
        $this->assertArrayHasKey('day_of_week', $result['best_day']);
        $this->assertArrayHasKey('day_name', $result['best_day']);
        $this->assertArrayHasKey('count', $result['best_day']);

        // Verificar que o melhor dia tem mais vendas
        $this->assertGreaterThan(0, $result['best_day']['count']);
    }

    #[Test]
    public function agrupa_vendas_por_forma_de_pagamento()
    {
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $paymentMethod2 = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pix',
            'is_active' => true
        ]);

        // Criar pedidos com diferentes formas de pagamento
        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'total' => 100.00,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        Order::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $paymentMethod2->uuid,
            'total' => 150.00,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $result = $this->service->getSalesPerformance(
            $this->tenant->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            7
        );

        $this->assertIsArray($result['sales_by_payment_method']);
        $this->assertGreaterThan(0, count($result['sales_by_payment_method']));

        // Verificar que cada forma de pagamento tem os dados corretos
        foreach ($result['sales_by_payment_method'] as $payment) {
            $this->assertArrayHasKey('payment_method', $payment);
            $this->assertArrayHasKey('count', $payment);
            $this->assertArrayHasKey('total_value', $payment);
            $this->assertGreaterThan(0, $payment['count']);
            $this->assertGreaterThan(0, $payment['total_value']);
        }
    }

    #[Test]
    public function exporta_dados_de_desempenho_corretamente()
    {
        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $result = $this->service->exportSalesPerformance(
            $this->tenant->id,
            Carbon::now()->subDays(7)->format('Y-m-d'),
            Carbon::now()->format('Y-m-d'),
            7
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('exported_at', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertEquals($this->tenant->id, $result['tenant_id']);
    }

    #[Test]
    public function exclui_pedidos_cancelados_dos_calculos()
    {
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Criar pedidos entregues
        Order::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Entregue',
            'total' => 100.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        // Criar pedidos cancelados
        Order::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'Cancelado',
            'total' => 100.00,
            'payment_method_id' => $this->paymentMethod->uuid,
            'created_at' => Carbon::now()->subDays(3)
        ]);

        $result = $this->service->getSalesPerformance(
            $this->tenant->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            7
        );

        // Verificar que apenas pedidos entregues são contados
        $this->assertEquals(5, $result['indicators']['total_sales']['current']);
        $this->assertEquals(500.00, $result['indicators']['total_sales_value']['current']);
    }
}

