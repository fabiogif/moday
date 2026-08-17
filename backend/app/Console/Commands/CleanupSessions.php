<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupSessions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:cleanup 
                            {--hours=24 : Número de horas de inatividade para considerar expirada}';

    /**
     * The console command description.
     */
    protected $description = 'Limpa sessões inativas da tabela sessions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Limpando sessões inativas...');
        
        $hours = (int) $this->option('hours');
        $inactiveTime = time() - ($hours * 3600);
        
        try {
            // Deletar sessões antigas
            $deleted = DB::table('sessions')
                ->where('last_activity', '<', $inactiveTime)
                ->delete();
            
            $this->info("✅ {$deleted} sessões deletadas com sucesso!");
            
            // Mostrar estatísticas
            $activeSessions = DB::table('sessions')
                ->where('last_activity', '>=', time() - 3600) // Ativas na última hora
                ->count();
            $totalSessions = DB::table('sessions')->count();
            
            $this->line("📊 Sessões ativas (última hora): {$activeSessions}");
            $this->line("📊 Total de sessões: {$totalSessions}");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erro ao limpar sessões: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
