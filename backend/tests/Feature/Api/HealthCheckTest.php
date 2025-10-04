<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /** @test */
    public function health_check_endpoint_retorna_status_ok()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version'
            ])
            ->assertJson([
                'status' => 'ok'
            ]);

        $this->assertNotEmpty($response->json('timestamp'));
        $this->assertNotEmpty($response->json('version'));
    }

    /** @test */
    public function health_check_endpoint_e_publico()
    {
        // Não deve requerer autenticação
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }

    /** @test */
    public function health_check_retorna_timestamp_valido()
    {
        $response = $this->getJson('/api/health');

        $timestamp = $response->json('timestamp');
        
        // Verifica se é um timestamp válido no formato ISO
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{6}Z$/',
            $timestamp
        );
    }
}
