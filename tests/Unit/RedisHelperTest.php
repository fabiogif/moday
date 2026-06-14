<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\RedisHelper;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class RedisHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RedisHelper::reset();
    }

    protected function tearDown(): void
    {
        RedisHelper::reset();
        parent::tearDown();
    }

    /**
     * Testa se RedisHelper detecta Redis disponível
     * Mocks Redis to avoid real connection attempts in test environment
     */
    public function test_redis_helper_detects_available_redis(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('ping')->once()->andReturn(true);
        Redis::shouldReceive('connection')->once()->andReturn($connection);

        $result = RedisHelper::isAvailable();

        $this->assertTrue($result);
    }

    /**
     * Testa se RedisHelper retorna driver correto quando Redis disponível
     */
    public function test_redis_helper_returns_correct_drivers_when_available(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('ping')->once()->andReturn(true);
        Redis::shouldReceive('connection')->once()->andReturn($connection);

        if (!RedisHelper::isAvailable()) {
            $this->markTestSkipped('Redis não disponível para teste');
        }

        $this->assertEquals('redis', RedisHelper::getCacheDriver());
        $this->assertEquals('redis', RedisHelper::getQueueDriver());
        $this->assertEquals('redis', RedisHelper::getSessionDriver());
        $this->assertEquals('reverb', RedisHelper::getBroadcastDriver());
    }

    /**
     * Testa reset do cache de disponibilidade
     */
    public function test_redis_helper_reset(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('ping')->twice()->andReturn(true);
        Redis::shouldReceive('connection')->twice()->andReturn($connection);

        RedisHelper::isAvailable();
        RedisHelper::reset();

        // Após reset, deve verificar novamente
        $result = RedisHelper::isAvailable();
        $this->assertIsBool($result);
    }
}
