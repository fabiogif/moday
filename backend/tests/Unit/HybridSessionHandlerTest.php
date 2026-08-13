<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Session\HybridSessionHandler;
use App\Helpers\RedisHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery;

class HybridSessionHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected HybridSessionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset static cache and mock Redis so HybridSessionHandler uses database fallback
        RedisHelper::reset();
        Redis::shouldReceive('connection')->andThrow(new \Exception('Redis not available in tests'));
        $this->handler = new HybridSessionHandler(7200);
    }

    protected function tearDown(): void
    {
        RedisHelper::reset();
        parent::tearDown();
    }

    /**
     * Testa gravação e leitura de sessão
     */
    public function test_can_write_and_read_session(): void
    {
        $sessionId = 'test_session_' . time();
        $data = 'test_data_' . time();

        // Escrever
        $writeResult = $this->handler->write($sessionId, $data);
        $this->assertTrue($writeResult);

        // Ler
        $readData = $this->handler->read($sessionId);
        $this->assertEquals($data, $readData);
    }

    /**
     * Testa gravação no database
     */
    public function test_writes_to_database(): void
    {
        $sessionId = 'test_db_session_' . time();
        $data = 'test_data';

        $this->handler->write($sessionId, $data);

        // Verificar no database
        $exists = DB::table('sessions')
            ->where('id', $sessionId)
            ->exists();
        $this->assertTrue($exists);
    }

    /**
     * Testa destruição de sessão
     */
    public function test_can_destroy_session(): void
    {
        $sessionId = 'test_destroy_' . time();
        $data = 'test_data';

        $this->handler->write($sessionId, $data);
        $this->handler->destroy($sessionId);

        // Verificar que foi removida
        $readData = $this->handler->read($sessionId);
        $this->assertEquals('', $readData);

        $exists = DB::table('sessions')
            ->where('id', $sessionId)
            ->exists();
        $this->assertFalse($exists);
    }

    /**
     * Testa garbage collection
     */
    public function test_garbage_collection_removes_old_sessions(): void
    {
        // Criar sessão antiga
        DB::table('sessions')->insert([
            'id' => 'old_session',
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => time() - 10000, // 10000 segundos atrás
        ]);

        // Executar GC com maxlifetime de 7200 segundos
        $deleted = $this->handler->gc(7200);
        $this->assertGreaterThan(0, $deleted);

        // Verificar que foi removida
        $exists = DB::table('sessions')
            ->where('id', 'old_session')
            ->exists();
        $this->assertFalse($exists);
    }

    /**
     * Testa que sessão retorna vazio quando não existe
     */
    public function test_returns_empty_for_non_existent_session(): void
    {
        $data = $this->handler->read('non_existent_session');
        $this->assertEquals('', $data);
    }
}
