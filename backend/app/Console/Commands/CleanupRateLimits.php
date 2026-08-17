<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupRateLimits extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rate-limits:cleanup 
                            {--days=7 : Número de dias de registros para manter}';

    /**
     * The console command description.
     */
    protected $description = 'Limpa registros expirados da tabela rate_limits';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Limpando rate limits expirados...');
        
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        try {
            // Deletar registros expirados
            $deleted = DB::table('rate_limits')
                ->where('expires_at', '<', Carbon::now())
                ->orWhere('created_at', '<', $cutoffDate)
                ->delete();
            
            $this->info("✅ {$deleted} registros deletados com sucesso!");
            
            // Mostrar estatísticas
            $remaining = DB::table('rate_limits')->count();
            $this->line("📊 Registros restantes: {$remaining}");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erro ao limpar rate limits: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
