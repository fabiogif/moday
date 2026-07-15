<?php

namespace App\RateLimiting;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\RedisHelper;
use Carbon\Carbon;

/**
 * Hybrid Rate Limiter
 * 
 * Rate limiting que funciona com Redis (rápido) ou Database (fallback).
 */
class HybridRateLimiter
{
    private bool $useRedis;
    
    public function __construct()
    {
        $this->useRedis = RedisHelper::isAvailable();
    }
    
    /**
     * Verificar se ultrapassou o limite de tentativas
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        if ($this->useRedis) {
            try {
                return $this->redisTooManyAttempts($key, $maxAttempts);
            } catch (\Exception $e) {
                $this->useRedis = false;
                Log::warning('Redis rate limiting failed, using database', [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $this->databaseTooManyAttempts($key, $maxAttempts);
    }
    
    /**
     * Incrementar tentativas
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        if ($this->useRedis) {
            try {
                return $this->redisHit($key, $decaySeconds);
            } catch (\Exception $e) {
                $this->useRedis = false;
                Log::warning('Redis rate limit hit failed, using database');
            }
        }
        
        return $this->databaseHit($key, $decaySeconds);
    }
    
    /**
     * Obter número de tentativas restantes
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = $this->attempts($key);
        return max(0, $maxAttempts - $attempts);
    }
    
    /**
     * Obter número de tentativas
     */
    public function attempts(string $key): int
    {
        if ($this->useRedis) {
            try {
                return (int) Cache::get($key, 0);
            } catch (\Exception $e) {
                $this->useRedis = false;
            }
        }
        
        return $this->databaseAttempts($key);
    }
    
    /**
     * Limpar tentativas
     */
    public function clear(string $key): void
    {
        if ($this->useRedis) {
            try {
                Cache::forget($key);
                Cache::forget($key . ':timer');
            } catch (\Exception $e) {
                $this->useRedis = false;
            }
        }
        
        $this->databaseClear($key);
    }
    
    /**
     * Obter tempo até reset (em segundos)
     */
    public function availableIn(string $key): int
    {
        if ($this->useRedis) {
            try {
                $timer = Cache::get($key . ':timer');
                return $timer ? max(0, $timer - time()) : 0;
            } catch (\Exception $e) {
                $this->useRedis = false;
            }
        }
        
        return $this->databaseAvailableIn($key);
    }
    
    // ========== Redis Methods ==========
    
    private function redisTooManyAttempts(string $key, int $maxAttempts): bool
    {
        if (Cache::has($key . ':timer')) {
            $attempts = Cache::get($key, 0);
            return $attempts >= $maxAttempts;
        }
        
        return false;
    }
    
    private function redisHit(string $key, int $decaySeconds): int
    {
        Cache::add($key . ':timer', time() + $decaySeconds, $decaySeconds);
        
        $added = Cache::add($key, 0, $decaySeconds);
        $hits = (int) Cache::increment($key);
        
        if (!$added && $hits == 1) {
            $hits = (int) Cache::increment($key);
        }
        
        return $hits;
    }
    
    // ========== Database Methods ==========
    
    private function databaseTooManyAttempts(string $key, int $maxAttempts): bool
    {
        $record = DB::table('rate_limits')
            ->where('key', $key)
            ->where('expires_at', '>', Carbon::now())
            ->first();
            
        return $record && $record->attempts >= $maxAttempts;
    }
    
    private function databaseHit(string $key, int $decaySeconds): int
    {
        $expiresAt = Carbon::now()->addSeconds($decaySeconds);
        
        // Buscar registro existente
        $record = DB::table('rate_limits')
            ->where('key', $key)
            ->where('expires_at', '>', Carbon::now())
            ->first();
            
        if ($record) {
            // Incrementar tentativas
            DB::table('rate_limits')
                ->where('id', $record->id)
                ->increment('attempts');
            return $record->attempts + 1;
        } else {
            // Criar novo registro
            DB::table('rate_limits')->insert([
                'key' => $key,
                'attempts' => 1,
                'expires_at' => $expiresAt,
                'created_at' => Carbon::now(),
            ]);
            return 1;
        }
    }
    
    private function databaseAttempts(string $key): int
    {
        $record = DB::table('rate_limits')
            ->where('key', $key)
            ->where('expires_at', '>', Carbon::now())
            ->first();
            
        return $record ? $record->attempts : 0;
    }
    
    private function databaseClear(string $key): void
    {
        DB::table('rate_limits')
            ->where('key', $key)
            ->delete();
    }
    
    private function databaseAvailableIn(string $key): int
    {
        $record = DB::table('rate_limits')
            ->where('key', $key)
            ->where('expires_at', '>', Carbon::now())
            ->first();
            
        if (!$record) {
            return 0;
        }
        
        $expiresAt = Carbon::parse($record->expires_at);
        // Carbon 3 retorna diff assinado: now->diffInSeconds(future) > 0
        return max(0, (int) Carbon::now()->diffInSeconds($expiresAt, false));
    }
    
    /**
     * Cleanup de registros expirados (executar via cron)
     */
    public function cleanup(): int
    {
        try {
            return DB::table('rate_limits')
                ->where('expires_at', '<', Carbon::now())
                ->delete();
        } catch (\Exception $e) {
            Log::error('Rate limit cleanup failed: ' . $e->getMessage());
            return 0;
        }
    }
}
