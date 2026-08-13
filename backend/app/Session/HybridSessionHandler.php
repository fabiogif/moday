<?php

namespace App\Session;

use SessionHandlerInterface;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\RedisHelper;

/**
 * Hybrid Session Handler
 * 
 * Armazena sessões tanto no Redis (rápido) quanto no Database (backup).
 * Se o Redis falhar, usa o Database automaticamente.
 */
class HybridSessionHandler implements SessionHandlerInterface
{
    private int $lifetime;
    private bool $useRedis;
    
    public function __construct(int $lifetime = 7200)
    {
        $this->lifetime = $lifetime;
        $this->useRedis = RedisHelper::isAvailable();
    }
    
    /**
     * Abrir sessão
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }
    
    /**
     * Fechar sessão
     */
    public function close(): bool
    {
        return true;
    }
    
    /**
     * Ler dados da sessão
     */
    public function read($sessionId): string
    {
        // Tenta Redis primeiro (mais rápido)
        if ($this->useRedis) {
            try {
                $data = Redis::get("laravel_session:{$sessionId}");
                if ($data) {
                    return base64_decode($data);
                }
            } catch (\Exception $e) {
                $this->useRedis = false;
                Log::warning('Redis session read failed, falling back to database', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Fallback para Database
        try {
            $session = DB::table('sessions')
                ->where('id', $sessionId)
                ->first();
                
            return $session ? $session->payload : '';
        } catch (\Exception $e) {
            Log::error('Database session read failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
    
    /**
     * Escrever dados da sessão
     */
    public function write($sessionId, $data): bool
    {
        $userId = $this->extractUserId($data);
        $lastActivity = time();
        $redisSuccess = false;
        $dbSuccess = false;
        
        // Tenta escrever no Redis (não-bloqueante)
        if ($this->useRedis) {
            try {
                $encoded = base64_encode($data);
                Redis::setex("laravel_session:{$sessionId}", $this->lifetime, $encoded);
                $redisSuccess = true;
            } catch (\Exception $e) {
                $this->useRedis = false;
                Log::warning('Redis session write failed', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Sempre tenta escrever no Database (backup)
        try {
            DB::table('sessions')->updateOrInsert(
                ['id' => $sessionId],
                [
                    'user_id' => $userId,
                    'ip_address' => request()->ip(),
                    'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
                    'payload' => $data,
                    'last_activity' => $lastActivity,
                ]
            );
            $dbSuccess = true;
        } catch (\Exception $e) {
            Log::error('Database session write failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
        
        // Sucesso se pelo menos um funcionou
        return $redisSuccess || $dbSuccess;
    }
    
    /**
     * Destruir sessão
     */
    public function destroy($sessionId): bool
    {
        $success = false;
        
        // Deletar do Redis
        if ($this->useRedis) {
            try {
                Redis::del("laravel_session:{$sessionId}");
                $success = true;
            } catch (\Exception $e) {
                Log::warning('Redis session destroy failed', [
                    'session_id' => $sessionId
                ]);
            }
        }
        
        // Deletar do Database
        try {
            DB::table('sessions')->where('id', $sessionId)->delete();
            $success = true;
        } catch (\Exception $e) {
            Log::error('Database session destroy failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
        
        return $success;
    }
    
    /**
     * Garbage collection - limpar sessões antigas
     */
    public function gc($maxlifetime): int|false
    {
        try {
            $deleted = DB::table('sessions')
                ->where('last_activity', '<', time() - $maxlifetime)
                ->delete();
                
            Log::info("Session GC: deleted {$deleted} old sessions");
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Session GC failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extrair user_id do payload da sessão
     */
    private function extractUserId(string $data): ?int
    {
        try {
            $payload = unserialize($data);
            return $payload['login_web_' . sha1(static::class)] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
