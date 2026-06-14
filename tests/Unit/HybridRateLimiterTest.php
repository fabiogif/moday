<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\RateLimiting\HybridRateLimiter;
use App\Helpers\RedisHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery;

class HybridRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected HybridRateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset static cache and mock Redis so HybridRateLimiter uses database fallback
        RedisHelper::reset();
        Redis::shouldReceive('connection')->andThrow(new \Exception('Redis not available in tests'));
        $this->limiter = new HybridRateLimiter();
    }

    protected function tearDown(): void
    {
        RedisHelper::reset();
        parent::tearDown();
    }

    /**
     * Testa incremento de tentativas
     */
    public function test_can_increment_attempts(): void
    {
        $key = 'test_rate_limit_' . time();

        $hits = $this->limiter->hit($key, 60);
        $this->assertEquals(1, $hits);

        $hits = $this->limiter->hit($key, 60);
        $this->assertEquals(2, $hits);
    }

    /**
     * Testa verificação de limite excedido
     */
    public function test_detects_too_many_attempts(): void
    {
        $key = 'test_limit_' . time();
        $maxAttempts = 3;

        // Fazer 3 tentativas
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->hit($key, 60);
        }

        // Deve detectar que excedeu
        $this->assertTrue($this->limiter->tooManyAttempts($key, $maxAttempts));
    }

    /**
     * Testa obtenção de tentativas restantes
     */
    public function test_retrieves_retries_left(): void
    {
        $key = 'test_retries_' . time();
        $maxAttempts = 5;

        $this->limiter->hit($key, 60);
        $this->limiter->hit($key, 60);

        $retriesLeft = $this->limiter->retriesLeft($key, $maxAttempts);
        $this->assertEquals(3, $retriesLeft);
    }

    /**
     * Testa limpeza de tentativas
     */
    public function test_can_clear_attempts(): void
    {
        $key = 'test_clear_' . time();

        $this->limiter->hit($key, 60);
        $this->limiter->hit($key, 60);

        $this->limiter->clear($key);

        $attempts = $this->limiter->attempts($key);
        $this->assertEquals(0, $attempts);
    }

    /**
     * Testa cleanup de registros expirados
     */
    public function test_cleanup_removes_expired_records(): void
    {
        // Criar registro já expirado
        DB::table('rate_limits')->insert([
            'key' => 'expired_key',
            'attempts' => 5,
            'expires_at' => now()->subHour(),
            'created_at' => now()->subHour(),
        ]);

        $deleted = $this->limiter->cleanup();
        $this->assertGreaterThan(0, $deleted);

        // Verificar que foi removido
        $exists = DB::table('rate_limits')
            ->where('key', 'expired_key')
            ->exists();
        $this->assertFalse($exists);
    }
}
