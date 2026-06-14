<?php

/**
 * 🔧 EXEMPLOS DE IMPLEMENTAÇÃO - REDIS RESILIENCE
 * 
 * Este arquivo contém exemplos práticos de código para implementar
 * as melhorias propostas na análise de resiliência do Redis.
 * 
 * NÃO é para ser usado diretamente - serve como referência.
 */

namespace App\Examples;

// ====================================================================
// EXEMPLO 1: HYBRID SESSION HANDLER
// ====================================================================

use SessionHandlerInterface;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HybridSessionHandler implements SessionHandlerInterface
{
    private bool $useRedis = true;
    private int $lifetime;
    
    public function __construct(int $lifetime = 7200)
    {
        $this->lifetime = $lifetime;
        $this->checkRedisAvailability();
    }
    
    private function checkRedisAvailability(): void
    {
        try {
            Redis::connection()->ping();
            $this->useRedis = true;
        } catch (\Exception $e) {
            $this->useRedis = false;
            Log::warning('Redis unavailable, using database for sessions');
        }
    }
    
    public function read($sessionId): string
    {
        // Tenta Redis primeiro
        if ($this->useRedis) {
            try {
                $data = Redis::get("session:{$sessionId}");
                if ($data) {
                    return $data;
                }
            } catch (\Exception $e) {
                $this->useRedis = false;
                Log::error('Redis read failed, falling back to database');
            }
        }
        
        // Fallback para Database
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->first();
            
        return $session ? $session->payload : '';
    }
    
    public function write($sessionId, $data): bool
    {
        $expiration = time() + $this->lifetime;
        
        // Tenta escrever em ambos (Redis + Database)
        $redisSuccess = false;
        $dbSuccess = false;
        
        // Redis (não-bloqueante)
        if ($this->useRedis) {
            try {
                Redis::setex("session:{$sessionId}", $this->lifetime, $data);
                $redisSuccess = true;
            } catch (\Exception $e) {
                $this->useRedis = false;
                Log::error('Redis write failed');
            }
        }
        
        // Database (sempre tenta)
        try {
            DB::table('sessions')->updateOrInsert(
                ['id' => $sessionId],
                [
                    'user_id' => null, // Extrair do payload se necessário
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'payload' => $data,
                    'last_activity' => time(),
                ]
            );
            $dbSuccess = true;
        } catch (\Exception $e) {
            Log::error('Database session write failed: ' . $e->getMessage());
        }
        
        // Sucesso se pelo menos um funcionou
        return $redisSuccess || $dbSuccess;
    }
    
    public function destroy($sessionId): bool
    {
        $success = false;
        
        // Deletar do Redis
        if ($this->useRedis) {
            try {
                Redis::del("session:{$sessionId}");
                $success = true;
            } catch (\Exception $e) {
                Log::error('Redis session destroy failed');
            }
        }
        
        // Deletar do Database
        try {
            DB::table('sessions')->where('id', $sessionId)->delete();
            $success = true;
        } catch (\Exception $e) {
            Log::error('Database session destroy failed');
        }
        
        return $success;
    }
    
    public function gc($maxlifetime): int|false
    {
        // Cleanup de sessões antigas no database
        try {
            $deleted = DB::table('sessions')
                ->where('last_activity', '<', time() - $maxlifetime)
                ->delete();
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Session GC failed: ' . $e->getMessage());
            return false;
        }
    }
    
    public function open($savePath, $sessionName): bool
    {
        return true;
    }
    
    public function close(): bool
    {
        return true;
    }
}

// ====================================================================
// EXEMPLO 2: HYBRID RATE LIMITER
// ====================================================================

use Illuminate\Cache\RateLimiter as BaseRateLimiter;
use Illuminate\Support\Facades\Cache;

class HybridRateLimiter extends BaseRateLimiter
{
    private bool $useRedis = true;
    
    /**
     * Verifica se pode fazer request (rate limit)
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        // Tenta Redis primeiro
        if ($this->useRedis) {
            try {
                $attempts = $this->attempts($key);
                return $attempts >= $maxAttempts;
            } catch (\Exception $e) {
                $this->useRedis = false;
                Log::warning('Redis rate limiting failed, using database');
            }
        }
        
        // Fallback para Database
        return $this->databaseTooManyAttempts($key, $maxAttempts);
    }
    
    /**
     * Incrementa tentativas
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        if ($this->useRedis) {
            try {
                Cache::add($key.':timer', time() + $decaySeconds, $decaySeconds);
                $hits = Cache::increment($key);
                
                // Também salva no database em background
                $this->databaseHit($key, $decaySeconds);
                
                return $hits;
            } catch (\Exception $e) {
                $this->useRedis = false;
            }
        }
        
        // Fallback para Database
        return $this->databaseHit($key, $decaySeconds);
    }
    
    /**
     * Rate limit usando database
     */
    private function databaseTooManyAttempts(string $key, int $maxAttempts): bool
    {
        $record = DB::table('rate_limits')
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();
            
        return $record && $record->attempts >= $maxAttempts;
    }
    
    /**
     * Incrementa no database
     */
    private function databaseHit(string $key, int $decaySeconds): int
    {
        $expiresAt = now()->addSeconds($decaySeconds);
        
        $record = DB::table('rate_limits')
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();
            
        if ($record) {
            DB::table('rate_limits')
                ->where('id', $record->id)
                ->increment('attempts');
            return $record->attempts + 1;
        } else {
            DB::table('rate_limits')->insert([
                'key' => $key,
                'attempts' => 1,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]);
            return 1;
        }
    }
}

// ====================================================================
// EXEMPLO 3: MULTI-LEVEL CACHE STORE
// ====================================================================

use Illuminate\Contracts\Cache\Store;

class MultiLevelCacheStore implements Store
{
    private $redisStore;
    private $databaseStore;
    private $fileStore;
    
    public function __construct()
    {
        $this->redisStore = Cache::store('redis');
        $this->databaseStore = Cache::store('database');
        $this->fileStore = Cache::store('file');
    }
    
    public function get($key)
    {
        // Level 1: Redis (mais rápido)
        try {
            $value = $this->redisStore->get($key);
            if ($value !== null) {
                return $value;
            }
        } catch (\Exception $e) {
            Log::debug('Redis cache miss: ' . $key);
        }
        
        // Level 2: Database (médio)
        try {
            $value = $this->databaseStore->get($key);
            if ($value !== null) {
                // Repopular Redis em background
                dispatch(function() use ($key, $value) {
                    try {
                        $this->redisStore->put($key, $value, 3600);
                    } catch (\Exception $e) {}
                })->afterResponse();
                
                return $value;
            }
        } catch (\Exception $e) {
            Log::debug('Database cache miss: ' . $key);
        }
        
        // Level 3: File (mais lento, mas sempre funciona)
        return $this->fileStore->get($key);
    }
    
    public function put($key, $value, $seconds)
    {
        $success = false;
        
        // Tenta salvar em todos os níveis
        try {
            $this->redisStore->put($key, $value, $seconds);
            $success = true;
        } catch (\Exception $e) {}
        
        try {
            $this->databaseStore->put($key, $value, $seconds);
            $success = true;
        } catch (\Exception $e) {}
        
        try {
            $this->fileStore->put($key, $value, $seconds);
            $success = true;
        } catch (\Exception $e) {}
        
        return $success;
    }
    
    public function forget($key)
    {
        // Deletar de todos os níveis
        try { $this->redisStore->forget($key); } catch (\Exception $e) {}
        try { $this->databaseStore->forget($key); } catch (\Exception $e) {}
        try { $this->fileStore->forget($key); } catch (\Exception $e) {}
        
        return true;
    }
    
    public function flush()
    {
        try { $this->redisStore->flush(); } catch (\Exception $e) {}
        try { $this->databaseStore->flush(); } catch (\Exception $e) {}
        try { $this->fileStore->flush(); } catch (\Exception $e) {}
        
        return true;
    }
    
    // Implementar outros métodos do Store interface...
    public function many(array $keys) { /* ... */ }
    public function putMany(array $values, $seconds) { /* ... */ }
    public function increment($key, $value = 1) { /* ... */ }
    public function decrement($key, $value = 1) { /* ... */ }
    public function forever($key, $value) { /* ... */ }
    public function getPrefix() { return ''; }
}

// ====================================================================
// EXEMPLO 4: QUEUE FAILURE HANDLER MIDDLEWARE
// ====================================================================

use Illuminate\Queue\Middleware\RateLimited;

class QueueFailureHandler
{
    public function handle($job, $next)
    {
        try {
            return $next($job);
        } catch (\Predis\Connection\ConnectionException $e) {
            // Redis falhou - re-enfileirar no database
            Log::warning('Redis queue failed, retrying with database queue');
            
            // Muda temporariamente para database queue
            config(['queue.default' => 'database']);
            
            // Re-dispatch o job
            dispatch($job)->onQueue('database');
            
            // Não falha o job original
            return;
        } catch (\Exception $e) {
            // Outros erros - deixa o Laravel tratar normalmente
            throw $e;
        }
    }
}

// ====================================================================
// EXEMPLO 5: REDIS HEALTH CHECK
// ====================================================================

use Illuminate\Console\Command;

class RedisHealthCheck extends Command
{
    protected $signature = 'redis:health';
    protected $description = 'Check Redis health and switch to fallback if needed';
    
    public function handle()
    {
        $this->info('Checking Redis health...');
        
        try {
            $start = microtime(true);
            $result = Redis::ping();
            $latency = (microtime(true) - $start) * 1000;
            
            if ($result && $latency < 100) {
                $this->info("✅ Redis is healthy (latency: {$latency}ms)");
                
                // Redis está bom - pode reativar
                if (Cache::get('redis_fallback_active')) {
                    $this->switchToRedis();
                }
                
                return 0;
            } else {
                $this->warn("⚠️  Redis latency high: {$latency}ms");
            }
        } catch (\Exception $e) {
            $this->error('❌ Redis is down: ' . $e->getMessage());
            $this->switchToFallback();
            return 1;
        }
    }
    
    private function switchToFallback()
    {
        Cache::store('file')->put('redis_fallback_active', true, 3600);
        
        // Notificar admins
        // TODO: Enviar email/slack
        
        $this->info('Switched to fallback stores');
    }
    
    private function switchToRedis()
    {
        Cache::store('file')->forget('redis_fallback_active');
        $this->info('Switched back to Redis');
    }
}

// ====================================================================
// EXEMPLO 6: MIGRATION PARA SESSIONS TABLE
// ====================================================================

/**
 * Migration: create_sessions_table.php
 */
/*
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            
            // Para cleanup eficiente
            $table->index(['last_activity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
*/

// ====================================================================
// EXEMPLO 7: MIGRATION PARA RATE LIMITS TABLE
// ====================================================================

/**
 * Migration: create_rate_limits_table.php
 */
/*
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->integer('attempts')->default(1);
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at');
            
            // Índice composto para queries eficientes
            $table->index(['key', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limits');
    }
};
*/

// ====================================================================
// EXEMPLO 8: CONFIGURAÇÃO NO AppServiceProvider
// ====================================================================

/**
 * app/Providers/AppServiceProvider.php
 */
/*
public function register(): void
{
    // Registrar Hybrid Session Handler
    $this->app->singleton('session.handler', function ($app) {
        return new \App\Session\HybridSessionHandler(
            config('session.lifetime') * 60
        );
    });
    
    // Registrar Multi-Level Cache
    $this->app->singleton('cache.multilevel', function ($app) {
        return new \App\Cache\MultiLevelCacheStore();
    });
}

public function boot(): void
{
    // Health check periódico do Redis
    if ($this->app->runningInConsole()) {
        $this->commands([
            \App\Console\Commands\RedisHealthCheck::class,
        ]);
    }
    
    // Agendar cleanup de sessões antigas
    if ($this->app->runningInConsole()) {
        Schedule::command('session:gc')->hourly();
    }
}
*/

// ====================================================================
// EXEMPLO 9: FRONTEND POLLING FALLBACK (TypeScript)
// ====================================================================

/**
 * Frontend: lib/websocket-with-fallback.ts
 */
/*
class WebSocketWithFallback {
    private ws: WebSocket | null = null;
    private polling: NodeJS.Timeout | null = null;
    private lastEventId: string | null = null;
    
    connect(url: string) {
        try {
            this.ws = new WebSocket(url);
            
            this.ws.onopen = () => {
                console.log('WebSocket connected');
                this.stopPolling();
            };
            
            this.ws.onerror = () => {
                console.warn('WebSocket failed, falling back to polling');
                this.startPolling();
            };
            
            this.ws.onclose = () => {
                console.warn('WebSocket closed, falling back to polling');
                this.startPolling();
            };
            
        } catch (error) {
            console.error('WebSocket error:', error);
            this.startPolling();
        }
    }
    
    private startPolling() {
        if (this.polling) return;
        
        this.polling = setInterval(async () => {
            try {
                const response = await fetch('/api/events/poll', {
                    method: 'POST',
                    body: JSON.stringify({ lastEventId: this.lastEventId }),
                });
                
                const events = await response.json();
                events.forEach(event => {
                    this.handleEvent(event);
                    this.lastEventId = event.id;
                });
                
            } catch (error) {
                console.error('Polling error:', error);
            }
        }, 5000); // Poll a cada 5 segundos
    }
    
    private stopPolling() {
        if (this.polling) {
            clearInterval(this.polling);
            this.polling = null;
        }
    }
    
    private handleEvent(event: any) {
        // Processar evento
        console.log('Event received:', event);
    }
}
*/
