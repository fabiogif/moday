<?php

namespace Tests\Feature\Reports;

use PHPUnit\Framework\Attributes\Test;

class MonthlyFinancialReportTest extends ReportTestCase
{
    #[Test]
    public function pode_gerar_relatorio_financeiro_mensal_csv()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/monthly-financial', [
                'month' => now()->month,
                'year' => now()->year,
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('Dia', $content);
        $this->assertStringContainsString('Receita', $content);
    }
    
    #[Test]
    public function valida_mes_obrigatorio()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/monthly-financial', [
                'year' => 2025,
                'format' => 'csv',
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['month']);
    }
    
    #[Test]
    public function valida_mes_entre_1_e_12()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/monthly-financial', [
                'month' => 13, // Inválido
                'year' => 2025,
                'format' => 'csv',
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['month']);
    }
    
    #[Test]
    public function valida_ano_minimo()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/monthly-financial', [
                'month' => 10,
                'year' => 2019, // Abaixo do mínimo (2020)
                'format' => 'csv',
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['year']);
    }
}

