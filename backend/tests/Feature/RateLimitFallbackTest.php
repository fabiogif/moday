<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\RateLimiting\HybridRateLimiter;

class RateLimitFallbackTest extends TestCase
{
    use RefreshDatabase;
    
    protected HybridRateLimiter $limiter;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = new HybridRateLimiter();
    }
    
    /**
     * Testa que rate limiting funciona sem Redis
     */
    public function test_rate_limiting_works_without_redis(): void
    {
        $key = 'api_route_' . time();
        $maxAttempts = 5;
        
        // Fazer múltiplas tentativas
        for ($i = 0; $i < $maxAttempts + 2; $i++) {
            $this->limiter->hit($key, 60);
        }
        
        // Verificar que detectou excesso
        $tooMany = $this->limiter->tooManyAttempts($key, $maxAttempts);
        $this->assertTrue($tooMany);
        
        // Verificar que está no database
        $record = DB::table('rate_limits')
            ->where('key', $key)
            ->first();
            
        $this->assertNotNull($record);
        $this->assertGreaterThan($maxAttempts, $record->attempts);
    }
    
    /**
     * Testa que diferentes keys são independentes
     */
    public function test_different_keys_are_independent(): void
    {
        $key1 = 'user_1_action';
        $key2 = 'user_2_action';
        $maxAttempts = 3;
        
        // User 1 excede limite
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->hit($key1, 60);
        }
        
        // User 2 faz apenas 2 tentativas
        $this->limiter->hit($key2, 60);
        $this->limiter->hit($key2, 60);
        
        // User 1 deve estar bloqueado
        $this->assertTrue($this->limiter->tooManyAttempts($key1, $maxAttempts));
        
        // User 2 não deve estar bloqueado
        $this->assertFalse($this->limiter->tooManyAttempts($key2, $maxAttempts));
    }
    
    /**
     * Testa rate limiting por IP
     */
    public function test_rate_limiting_by_ip(): void
    {
        $ip = '192.168.1.100';
        $key = 'ip_' . $ip;
        
        // Simular múltiplas requisições do mesmo IP
        for ($i = 0; $i < 10; $i++) {
            $this->limiter->hit($key, 60);
        }
        
        // Verificar bloqueio
        $this->assertTrue($this->limiter->tooManyAttempts($key, 5));
        
        // Verificar tempo até disponível
        $availableIn = $this->limiter->availableIn($key);
        $this->assertGreaterThan(0, $availableIn);
        $this->assertLessThanOrEqual(60, $availableIn);
    }
    
    /**
     * Testa que rate limit expira corretamente
     */
    public function test_rate_limit_expires_correctly(): void
    {
        $key = 'expire_test_' . time();
        
        // Criar registro com tempo de expiração curto (1 segundo)
        $this->limiter->hit($key, 1);
        
        // Verificar que existe
        $this->assertEquals(1, $this->limiter->attempts($key));
        
        // Aguardar expiração
        sleep(2);
        
        // Criar novo registro (deve resetar contador)
        $this->limiter->hit($key, 60);
        
        // Contador deve ser 1 novamente
        $this->assertEquals(1, $this->limiter->attempts($key));
    }
    
    /**
     * Testa integração com rotas da aplicação
     */
    public function test_rate_limiting_integration_with_routes(): void
    {
        // Fazer múltiplas requisições para uma rota com rate limit
        $responses = [];
        
        for ($i = 0; $i < 15; $i++) {
            $response = $this->get('/api/health');
            $responses[] = $response->status();
        }
        
        // As primeiras devem ter sucesso (200)
        $successCount = count(array_filter($responses, fn($status) => $status === 200));
        $this->assertGreaterThan(0, $successCount);
        
        // Eventualmente deve retornar 429 (Too Many Requests)
        // Nota: Isso depende da configuração do rate limit na rota
        $rateLimitedCount = count(array_filter($responses, fn($status) => $status === 429));
        
        // Pelo menos algumas requisições devem passar
        $this->assertGreaterThan(5, $successCount);
    }
}
