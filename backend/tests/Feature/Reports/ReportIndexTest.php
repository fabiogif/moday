<?php

namespace Tests\Feature\Reports;

use PHPUnit\Framework\Attributes\Test;

class ReportIndexTest extends ReportTestCase
{
    #[Test]
    public function pode_listar_relatorios_disponiveis()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/reports');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'formats',
                    'filters',
                ]
            ]
        ]);
        
        $data = $response->json('data');
        $this->assertCount(5, $data);
        
        // Verifica se todos os 5 relatórios estão presentes
        $reportIds = array_column($data, 'id');
        $this->assertContains('daily-sales', $reportIds);
        $this->assertContains('clients', $reportIds);
        $this->assertContains('top-products', $reportIds);
        $this->assertContains('monthly-financial', $reportIds);
        $this->assertContains('table-occupancy', $reportIds);
    }
    
    #[Test]
    public function nao_pode_listar_relatorios_sem_autenticacao()
    {
        $response = $this->getJson('/api/reports');
        
        $response->assertStatus(401);
    }
    
    #[Test]
    public function cada_relatorio_tem_formatos_definidos()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/reports');
        
        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        foreach ($data as $report) {
            $this->assertArrayHasKey('formats', $report);
            $this->assertIsArray($report['formats']);
            $this->assertContains('csv', $report['formats']);
            $this->assertContains('pdf', $report['formats']);
            $this->assertContains('excel', $report['formats']);
        }
    }
}

