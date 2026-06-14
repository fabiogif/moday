<?php

namespace Tests\Feature\Reports;

use PHPUnit\Framework\Attributes\Test;

class TableOccupancyReportTest extends ReportTestCase
{
    #[Test]
    public function pode_gerar_relatorio_ocupacao_mesas_csv()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/table-occupancy', [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('Mesa', $content);
        $this->assertStringContainsString('Ocupação', $content);
    }
    
    #[Test]
    public function valida_periodo_obrigatorio()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/table-occupancy', [
                'format' => 'csv',
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
    }
    
    #[Test]
    public function relatorio_filtra_apenas_pedidos_presenciais()
    {
        // Este teste verifica se apenas pedidos presenciais (não delivery) são considerados
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/table-occupancy', [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
        
        // O conteúdo não deve incluir pedidos de delivery
        $content = $response->getContent();
        $this->assertStringNotContainsString('Delivery', $content);
    }
}

