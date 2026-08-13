<?php

namespace Tests\Feature\Reports;

use PHPUnit\Framework\Attributes\Test;

class ClientsReportTest extends ReportTestCase
{
    #[Test]
    public function pode_gerar_relatorio_clientes_csv()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/clients', [
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('Nome', $content);
        $this->assertStringContainsString('CPF', $content);
        $this->assertStringContainsString('Email', $content);
    }
    
    #[Test]
    public function pode_filtrar_clientes_por_status()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/clients', [
                'is_active' => true,
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
    }
    
    #[Test]
    public function pode_filtrar_clientes_por_estado()
    {
        $this->createTestData();
        
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/clients', [
                'state' => 'SP',
                'format' => 'csv',
            ]);
        
        $response->assertStatus(200);
    }
    
    #[Test]
    public function valida_estado_maximo_2_caracteres()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/reports/clients', [
                'state' => 'SAO', // Mais de 2 caracteres
                'format' => 'csv',
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['state']);
    }
}

