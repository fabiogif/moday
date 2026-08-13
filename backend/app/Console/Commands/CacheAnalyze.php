<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheAnalyze extends Command
{
    protected $signature = 'cache:analyze 
                          {--stats : Mostrar estatísticas do Redis}
                          {--controllers : Analisar controllers}
                          {--keys : Listar chaves de cache}
                          {--clear= : Limpar cache por padrão}';
    
    protected $description = 'Analisa uso de cache na aplicação';

    public function handle(): int
    {
        $this->info('🔍 Análise de Cache');
        $this->newLine();

        if ($this->option('stats')) {
            $this->showRedisStats();
        }

        if ($this->option('controllers')) {
            $this->analyzeControllers();
        }

        if ($this->option('keys')) {
            $this->listCacheKeys();
        }

        if ($pattern = $this->option('clear')) {
            $this->clearCachePattern($pattern);
        }

        if (!$this->option('stats') && !$this->option('controllers') && !$this->option('keys') && !$this->option('clear')) {
            $this->showMenu();
        }

        return self::SUCCESS;
    }

    private function showMenu(): void
    {
        $choice = $this->choice(
            'O que você deseja fazer?',
            [
                'stats' => 'Ver estatísticas do Redis',
                'controllers' => 'Analisar controllers',
                'keys' => 'Listar chaves de cache',
                'clear' => 'Limpar cache',
                'exit' => 'Sair'
            ],
            'stats'
        );

        match($choice) {
            'stats' => $this->showRedisStats(),
            'controllers' => $this->analyzeControllers(),
            'keys' => $this->listCacheKeys(),
            'clear' => $this->clearCacheInteractive(),
            default => null
        };
    }

    private function showRedisStats(): void
    {
        $this->info('📊 Estatísticas do Redis');
        $this->newLine();

        try {
            // Verificar se Redis está disponível
            $ping = Cache::store('redis')->getRedis()->ping();
            if ($ping !== '+PONG' && $ping !== 'PONG') {
                $this->error('❌ Redis não disponível');
                return;
            }

            $this->info('✅ Redis Status: Online');
            $this->newLine();

            // Info do Redis
            $info = Cache::store('redis')->getRedis()->info();
            
            // Memória
            $this->line("💾 Memória:");
            $this->line("  └─ Usada: " . ($info['used_memory_human'] ?? 'N/A'));
            $this->line("  └─ Peak: " . ($info['used_memory_peak_human'] ?? 'N/A'));
            $this->newLine();

            // Chaves
            $dbSize = Cache::store('redis')->getRedis()->dbSize();
            $this->line("🔑 Chaves: {$dbSize}");
            $this->newLine();

            // Hit Rate
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            
            if ($total > 0) {
                $hitRate = round(($hits / $total) * 100, 2);
                $this->line("🎯 Cache Performance:");
                $this->line("  ├─ Hits: {$hits}");
                $this->line("  ├─ Misses: {$misses}");
                $this->line("  └─ Hit Rate: {$hitRate}%");
                
                if ($hitRate > 80) {
                    $this->info("     ✅ Excelente! (>80%)");
                } elseif ($hitRate > 60) {
                    $this->warn("     ⚠️  Bom (60-80%)");
                } else {
                    $this->error("     ❌ Baixo (<60%) - Revisar TTL");
                }
            } else {
                $this->warn("  └─ Nenhum acesso registrado ainda");
            }
            
            $this->newLine();

            // Uptime
            $uptime = $info['uptime_in_seconds'] ?? 0;
            $uptimeHours = round($uptime / 3600, 1);
            $this->line("⏱️  Uptime: {$uptimeHours} horas");
            
        } catch (\Exception $e) {
            $this->error("❌ Erro ao conectar ao Redis: " . $e->getMessage());
            $this->warn("💡 Verifique se o Redis está rodando: docker-compose up -d redis");
        }
    }

    private function analyzeControllers(): void
    {
        $this->info('🔍 Analisando Controllers...');
        $this->newLine();

        $controllersPath = app_path('Http/Controllers/Api');
        $controllers = File::glob($controllersPath . '/*ApiController.php');

        $withCache = [];
        $withoutCache = [];

        foreach ($controllers as $controller) {
            $content = File::get($controller);
            $name = basename($controller, '.php');
            
            // Verificar se usa Cache
            $hasCache = str_contains($content, 'Cache::remember') || 
                       str_contains($content, 'CacheService');
            
            if ($hasCache) {
                $withCache[] = $name;
            } else {
                $withoutCache[] = $name;
            }
        }

        // Controllers COM cache
        $this->info('✅ Controllers com Cache (' . count($withCache) . '):');
        if (empty($withCache)) {
            $this->warn('  └─ Nenhum encontrado');
        } else {
            foreach ($withCache as $controller) {
                $this->line("  ├─ {$controller}");
            }
        }
        $this->newLine();

        // Controllers SEM cache
        $this->warn('⚠️  Controllers sem Cache (' . count($withoutCache) . '):');
        if (empty($withoutCache)) {
            $this->info('  └─ Todos implementados!');
        } else {
            foreach ($withoutCache as $controller) {
                $this->line("  ├─ {$controller}");
            }
        }
        $this->newLine();

        // Progresso
        $total = count($withCache) + count($withoutCache);
        $percentage = $total > 0 ? round((count($withCache) / $total) * 100, 1) : 0;
        
        $this->info("📊 Progresso: {$percentage}% ({count($withCache)}/{$total})");
        
        if ($percentage < 30) {
            $this->error("❌ Implementação inicial - Priorize controllers principais");
        } elseif ($percentage < 70) {
            $this->warn("⚠️  Boa implementação - Continue com os restantes");
        } else {
            $this->info("✅ Excelente cobertura!");
        }
    }

    private function listCacheKeys(): void
    {
        $this->info('🔑 Chaves de Cache por Módulo');
        $this->newLine();

        try {
            $redis = Cache::store('redis')->getRedis();
            
            $patterns = [
                'product' => 'laravel_cache:product*',
                'category' => 'laravel_cache:categor*',
                'order' => 'laravel_cache:order*',
                'client' => 'laravel_cache:client*',
                'dashboard' => 'laravel_cache:dashboard*',
                'plan' => 'laravel_cache:plan*',
                'user' => 'laravel_cache:user*',
                'table' => 'laravel_cache:table*',
            ];

            $totalKeys = 0;

            foreach ($patterns as $name => $pattern) {
                $keys = $redis->keys($pattern);
                $count = count($keys);
                $totalKeys += $count;
                
                $icon = $count > 0 ? '✅' : '⚪';
                $this->line("{$icon} " . ucfirst($name) . ": {$count} chaves");
                
                if ($count > 0 && $count <= 5) {
                    foreach ($keys as $key) {
                        $ttl = $redis->ttl($key);
                        $ttlMin = $ttl > 0 ? round($ttl / 60) . 'min' : 'sem expiração';
                        $this->line("    └─ " . basename($key) . " (TTL: {$ttlMin})");
                    }
                }
            }

            $this->newLine();
            $this->info("📦 Total: {$totalKeys} chaves");

            if ($totalKeys === 0) {
                $this->warn("💡 Nenhuma chave de cache encontrada. Execute algumas requisições primeiro.");
            }

        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
        }
    }

    private function clearCacheInteractive(): void
    {
        $patterns = [
            'all' => 'Tudo (CUIDADO!)',
            'product' => 'Produtos',
            'category' => 'Categorias',
            'order' => 'Pedidos',
            'client' => 'Clientes',
            'dashboard' => 'Dashboard',
            'cancel' => 'Cancelar'
        ];

        $choice = $this->choice(
            'Qual cache deseja limpar?',
            $patterns,
            'cancel'
        );

        if ($choice === 'cancel') {
            $this->info('Operação cancelada');
            return;
        }

        if ($choice === 'all') {
            if (!$this->confirm('⚠️  Tem certeza que deseja limpar TODO o cache?', false)) {
                $this->info('Operação cancelada');
                return;
            }
        }

        $this->clearCachePattern($choice);
    }

    private function clearCachePattern(string $pattern): void
    {
        $this->info("🧹 Limpando cache: {$pattern}");

        try {
            if ($pattern === 'all') {
                Cache::flush();
                $this->info('✅ Todo o cache foi limpo');
            } else {
                $redis = Cache::store('redis')->getRedis();
                $searchPattern = "laravel_cache:{$pattern}*";
                $keys = $redis->keys($searchPattern);
                
                if (empty($keys)) {
                    $this->warn("⚠️  Nenhuma chave encontrada para: {$pattern}");
                    return;
                }

                foreach ($keys as $key) {
                    $redis->del($key);
                }

                $count = count($keys);
                $this->info("✅ {$count} chaves removidas");
            }
        } catch (\Exception $e) {
            $this->error("❌ Erro ao limpar cache: " . $e->getMessage());
        }
    }
}
