<?php

namespace Tests\Feature\Reports;

use PHPUnit\Framework\Attributes\Test;

class TopProductsReportTest extends ReportTestCase
{
    #[Test]
    public function pode_gerar_relatorio_top_produtos_csv()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/top-products', [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('Produto', $content);
        $this->assertStringContainsString('Qtd. Vendida', $content);
    }
    
    #[Test]
    public function pode_limitar_quantidade_de_produtos()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/top-products', [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'limit' => 5,
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
    }
    
    #[Test]
    public function valida_periodo_obrigatorio()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/top-products', [
                'format' => 'csv',
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
    }
    
    #[Test]
    public function valida_limite_maximo_100()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/top-products', [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'limit' => 150, // Acima do máximo
                'format' => 'csv',
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['limit']);
    }
}

