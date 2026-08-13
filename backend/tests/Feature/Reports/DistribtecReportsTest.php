<?php

namespace Tests\Feature\Reports;

use App\Models\Client;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\StockMovement;
use PHPUnit\Framework\Attributes\Test;

class DistribtecReportsTest extends ReportTestCase
{
    private function createDistribtecData(): void
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'company_name' => 'Farmácia Teste',
            'trade_name' => 'Farma Teste',
        ]);

        $productA = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto A',
            'qtd_stock' => 100,
        ]);

        $productB = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto B',
            'qtd_stock' => 50,
        ]);

        $order = SaleOrder::factory()->aprovado()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'subtotal' => 1000,
            'total' => 1000,
            'ordered_at' => now(),
        ]);

        SaleOrderItem::create([
            'sale_order_id' => $order->id,
            'product_id' => $productA->id,
            'quantity' => 10,
            'unit_price' => 80,
            'subtotal' => 800,
        ]);

        SaleOrderItem::create([
            'sale_order_id' => $order->id,
            'product_id' => $productB->id,
            'quantity' => 2,
            'unit_price' => 100,
            'subtotal' => 200,
        ]);

        StockMovement::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $productA->id,
            'type' => 'entrada',
            'quantity' => 50,
            'unit_cost' => 10,
        ]);

        StockMovement::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $productA->id,
            'type' => 'saida',
            'quantity' => 20,
            'unit_cost' => 10,
        ]);
    }

    #[Test]
    public function pode_visualizar_vendas_por_periodo()
    {
        $this->createDistribtecData();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/reports/sales?' . http_build_query([
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'summary' => ['orders_count', 'total_value', 'avg_ticket'],
                    'rows',
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data.rows')));
    }

    #[Test]
    public function pode_exportar_vendas_por_periodo_csv()
    {
        $this->createDistribtecData();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/sales', [
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'format' => 'csv',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('PV', $response->getContent());
    }

    #[Test]
    public function pode_visualizar_vendas_por_cliente()
    {
        $this->createDistribtecData();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/reports/sales-by-client?' . http_build_query([
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $rows = $response->json('data.rows');
        $this->assertNotEmpty($rows);
        $this->assertStringContainsString('Farma', $rows[0]['client']);
    }

    #[Test]
    public function pode_visualizar_curva_abc()
    {
        $this->createDistribtecData();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/reports/abc-products?' . http_build_query([
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $rows = $response->json('data.rows');
        $this->assertCount(2, $rows);
        $this->assertEquals('A', $rows[0]['abc_class']);
    }

    #[Test]
    public function pode_visualizar_giro_de_estoque()
    {
        $this->createDistribtecData();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/reports/stock-turnover?' . http_build_query([
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $rows = $response->json('data.rows');
        $this->assertNotEmpty($rows);
        $this->assertEquals('Produto A', $rows[0]['product']);
    }

    #[Test]
    public function relatorios_distribtec_requerem_autenticacao()
    {
        $response = $this->getJson('/api/reports/sales?' . http_build_query([
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(401);
    }

    #[Test]
    public function valida_periodo_obrigatorio()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/reports/sales');

        $response->assertStatus(422);
    }
}
