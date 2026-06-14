<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Helpers\RedisHelper;

class RedisHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'redis:health 
                            {--notify : Enviar notificação em caso de falha}';

    /**
     * The console command description.
     */
    protected $description = 'Verifica a saúde do Redis e ativa fallbacks se necessário';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Verificando saúde do Redis...');
        
        $isHealthy = $this->checkRedisHealth();
        
        if ($isHealthy) {
            $this->handleHealthyRedis();
            return Command::SUCCESS;
        } else {
            $this->handleUnhealthyRedis();
            return Command::FAILURE;
        }
    }
    
    /**
     * Verifica se Redis está saudável
     */
    private function checkRedisHealth(): bool
    {
        try {
            $start = microtime(true);
            $result = Redis::connection()->ping();
            $latency = (microtime(true) - $start) * 1000;
            
            if ($result && $latency < 100) {
                $this->info("✅ Redis está saudável (latência: " . round($latency, 2) . "ms)");
                return true;
            } else {
                $this->warn("⚠️  Redis com alta latência: " . round($latency, 2) . "ms");
                return $latency < 500; // Aceita até 500ms
            }
        } catch (\Exception $e) {
            $this->error('❌ Redis indisponível: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Redis está saudável - pode voltar a usar
     */
    private function handleHealthyRedis(): void
    {
        $wasFallback = Cache::store('file')->get('redis_fallback_active', false);
        
        if ($wasFallback) {
            $this->info('🔄 Redis recuperado! Voltando ao modo normal...');
            Cache::store('file')->forget('redis_fallback_active');
            RedisHelper::reset();
            
            // Log de recuperação
            Log::info('Redis recovered and switched back from fallback mode');
            
            if ($this->option('notify')) {
                $this->notifyAdmins('✅ Redis Recuperado', 'O Redis voltou a funcionar normalmente.');
            }
        }
    }
    
    /**
     * Redis não está saudável - ativar fallback
     */
    private function handleUnhealthyRedis(): void
    {
        $this->warn('⚠️  Ativando modo fallback...');
        
        // Marcar que está em modo fallback
        Cache::store('file')->put('redis_fallback_active', true, 3600);
        RedisHelper::reset();
        
        // Log de falha
        Log::warning('Redis is unhealthy, switched to fallback mode');
        
        $this->displayFallbackStatus();
        
        if ($this->option('notify')) {
            $this->notifyAdmins('🔴 Redis Falhou', 'Sistema em modo fallback (database/file)');
        }
    }
    
    /**
     * Mostrar status dos fallbacks
     */
    private function displayFallbackStatus(): void
    {
        $this->newLine();
        $this->info('📊 Status dos Sistemas:');
        $this->line('  • Cache: Redis → Database/File');
        $this->line('  • Sessões: Redis → Database');
        $this->line('  • Filas: Redis → Database');
        $this->line('  • Rate Limiting: Redis → Database');
        $this->line('  • Broadcasting: Reverb → Log');
        $this->newLine();
    }
    
    /**
     * Notificar administradores (placeholder)
     */
    private function notifyAdmins(string $subject, string $message): void
    {
        // TODO: Implementar notificação real (email, Slack, etc)
        $this->comment("📧 Notificação: {$subject} - {$message}");
        
        Log::info('Admin notification', [
            'subject' => $subject,
            'message' => $message
        ]);
    }
}
