<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RedisHelper
{
    private static ?bool $isAvailable = null;

    /**
     * Verifica se o Redis está disponível
     */
    public static function isAvailable(): bool
    {
        // Cache do resultado para evitar múltiplas tentativas
        if (self::$isAvailable !== null) {
            return self::$isAvailable;
        }

        // phpunit.xml / .env enviam "false" como string — usar filter_var
        if (! filter_var(env('REDIS_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
            self::$isAvailable = false;
            return false;
        }

        try {
            // Tenta fazer ping no Redis com timeout curto
            Redis::connection()->ping();
            self::$isAvailable = true;
            return true;
        } catch (\Throwable $e) {
            // Log apenas em debug mode
            if (config('app.debug')) {
                Log::info('Redis não disponível, usando fallback: ' . $e->getMessage());
            }
            self::$isAvailable = false;
            return false;
        }
    }

    /**
     * Retorna o driver de cache apropriado
     */
    public static function getCacheDriver(): string
    {
        return self::isAvailable() ? 'redis' : 'file';
    }

    /**
     * Retorna o driver de queue apropriado
     */
    public static function getQueueDriver(): string
    {
        return self::isAvailable() ? 'redis' : 'database';
    }

    /**
     * Retorna o driver de session apropriado
     */
    public static function getSessionDriver(): string
    {
        return self::isAvailable() ? 'redis' : 'file';
    }

    /**
     * Retorna o driver de broadcast apropriado
     */
    public static function getBroadcastDriver(): string
    {
        return self::isAvailable() ? 'reverb' : 'log';
    }

    /**
     * Reseta o cache de disponibilidade
     */
    public static function reset(): void
    {
        self::$isAvailable = null;
    }
}

