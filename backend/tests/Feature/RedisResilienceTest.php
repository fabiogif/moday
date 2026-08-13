<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Helpers\RedisHelper;

class RedisResilienceTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Testa fluxo completo com Redis funcionando
     */
    public function test_system_works_with_redis_available(): void
    {
        if (!RedisHelper::isAvailable()) {
            $this->markTestSkipped('Redis não disponível');
        }
        
        // Fazer requisição que usa cache
        $response = $this->get('/api/health');
        $response->assertStatus(200);
        
        // Fazer requisição que usa rate limiting
        $response = $this->get('/api/plans');
        $response->assertStatus(200);
    }
    
    /**
     * Testa que login JWT funciona (auth sem sessão clássica)
     */
    public function test_sessions_are_backed_up_to_database(): void
    {
        $user = \App\Models\User::factory()->create([
            'password' => bcrypt('password'),
        ]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.token'));

        $this->withHeader('Authorization', 'Bearer ' . $response->json('data.token'))
            ->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id);
    }
    
    /**
     * Testa rate limiting com database fallback
     */
    public function test_rate_limiting_works_with_database(): void
    {
        $limiter = new \App\RateLimiting\HybridRateLimiter();
        $key = 'test_api_rate_limit_' . time();
        
        // Fazer múltiplas requisições
        for ($i = 0; $i < 5; $i++) {
            $limiter->hit($key, 60);
        }
        
        // Verificar que atingiu o limite
        $tooMany = $limiter->tooManyAttempts($key, 3);
        $this->assertTrue($tooMany);
        
        // Verificar que está no database
        $exists = DB::table('rate_limits')
            ->where('key', $key)
            ->exists();
        $this->assertTrue($exists);
    }
    
    /**
     * Testa comando de health check
     */
    public function test_redis_health_check_command(): void
    {
        $exitCode = Artisan::call('redis:health');
        
        if (RedisHelper::isAvailable()) {
            $this->assertEquals(0, $exitCode);
        } else {
            $this->assertEquals(1, $exitCode);
        }
    }
    
    /**
     * Testa comando de cleanup de rate limits
     */
    public function test_rate_limits_cleanup_command(): void
    {
        // Criar registro expirado
        DB::table('rate_limits')->insert([
            'key' => 'expired_test',
            'attempts' => 5,
            'expires_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);
        
        $exitCode = Artisan::call('rate-limits:cleanup');
        $this->assertEquals(0, $exitCode);
        
        // Verificar que foi removido
        $exists = DB::table('rate_limits')
            ->where('key', 'expired_test')
            ->exists();
        $this->assertFalse($exists);
    }
    
    /**
     * Testa comando de cleanup de sessões
     */
    public function test_sessions_cleanup_command(): void
    {
        // Criar sessão antiga
        DB::table('sessions')->insert([
            'id' => 'old_test_session',
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => time() - 100000,
        ]);
        
        $exitCode = Artisan::call('sessions:cleanup', ['--hours' => 1]);
        $this->assertEquals(0, $exitCode);
        
        // Verificar que foi removida
        $exists = DB::table('sessions')
            ->where('id', 'old_test_session')
            ->exists();
        $this->assertFalse($exists);
    }
}
