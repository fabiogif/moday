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

        // As migrações são gerenciadas automaticamente pelo trait RefreshDatabase
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
     * Concede ao usuário de teste o profile "Administrador" com todas as permissões
     * do tenant, replicando o que acontece de verdade no registro de um tenant
     * (App\Services\TenantAclProvisioner). Necessário desde que as rotas de
     * produtos/mesas/clientes/módulos financeiros passaram a exigir acl.permission.
     */
    protected function grantFullAccess(\App\Models\User $user, \App\Models\Tenant $tenant): void
    {
        app(\App\Services\TenantAclProvisioner::class)->provisionAndAssignOwner($tenant, $user);
        $user->clearPermissionsCache();
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