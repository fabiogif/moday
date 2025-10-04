<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurações específicas para testes
        config([
            'jwt.ttl' => 60, // 1 hora para testes
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);

        // Executa as migrações necessárias para os testes
        $this->artisan('migrate');
    }

    /**
     * Cria um usuário autenticado para testes
     */
    protected function authenticatedUser(array $attributes = [])
    {
        $user = \App\Models\User::factory()->create($attributes);
        $token = auth('api')->login($user);
        
        return [
            'user' => $user,
            'token' => $token,
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ];
    }

    /**
     * Helper para fazer requisições autenticadas
     */
    protected function actingAsUser($user = null)
    {
        if (!$user) {
            $user = \App\Models\User::factory()->create();
        }

        $token = auth('api')->login($user);
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Helper para limpar cache entre testes
     */
    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        parent::tearDown();
    }
}