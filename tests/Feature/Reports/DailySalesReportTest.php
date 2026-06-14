<?php

namespace Tests\Feature\Reports;

use PHPUnit\Framework\Attributes\Test;

class DailySalesReportTest extends ReportTestCase
{
    #[Test]
    public function pode_gerar_relatorio_vendas_diario_csv()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/daily-sales', [
                'date' => now()->format('Y-m-d'),
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('ID', $content);
        $this->assertStringContainsString('Data/Hora', $content);
        $this->assertStringContainsString('Cliente', $content);
    }
    
    #[Test]
    public function pode_gerar_relatorio_vendas_com_filtro_periodo()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/daily-sales', [
                'start_date' => now()->subDays(7)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
    }
    
    #[Test]
    public function nao_pode_gerar_relatorio_sem_autenticacao()
    {
        $response = $this->postJson('/api/reports/daily-sales', [
            'date' => now()->format('Y-m-d'),
            'format' => 'csv',
        ]);
        
        $response->assertStatus(401);
    }
    
    #[Test]
    public function valida_formato_obrigatorio()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/daily-sales', [
                'date' => now()->format('Y-m-d'),
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);
    }
    
    #[Test]
    public function valida_formato_invalido()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/daily-sales', [
                'date' => now()->format('Y-m-d'),
                'format' => 'xml', // Formato inválido
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);
    }
    
    #[Test]
    public function valida_data_final_maior_que_inicial()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/daily-sales', [
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->subDays(7)->format('Y-m-d'), // Data final menor
                'format' => 'csv',
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }
}

