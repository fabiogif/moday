<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Transiciona lotes vencidos para status expired às 00:30 todos os dias
        $schedule->job(new \App\Jobs\ExpireBatchesJob())
            ->dailyAt('00:30')
            ->name('expire-batches')
            ->withoutOverlapping();

        // Expira trials automaticamente às 00:00 todos os dias
        $schedule->job(new \App\Jobs\ExpireTrialAccounts())
            ->dailyAt('00:00')
            ->name('expire-trial-accounts')
            ->withoutOverlapping();

        // Envia notificações de trials próximos da expiração às 09:00 todos os dias
        $schedule->job(new \App\Jobs\SendTrialNotifications())
            ->dailyAt('09:00')
            ->name('send-trial-notifications')
            ->withoutOverlapping();

        // ===== Admin System Jobs =====
        
        // Coleta métricas diárias de todos os tenants às 23:00
        $schedule->job(new \App\Jobs\CollectTenantMetrics())
            ->dailyAt('23:00')
            ->name('collect-tenant-metrics')
            ->withoutOverlapping();

        // Atualiza faturas vencidas a cada hora
        $schedule->job(new \App\Jobs\UpdateOverdueInvoices())
            ->hourly()
            ->name('update-overdue-invoices')
            ->withoutOverlapping();

        // Envia alertas administrativos às 08:00 todos os dias
        $schedule->job(new \App\Jobs\SendAdminAlerts())
            ->dailyAt('08:00')
            ->name('send-admin-alerts')
            ->withoutOverlapping();

        // Arquiva pedidos com mais de 1 ano às 02:00 todos os dias
        $schedule->job(new \App\Jobs\ArchiveOldOrders())
            ->dailyAt('02:00')
            ->name('archive-old-orders')
            ->withoutOverlapping();

        // Mantém tokens do iFood renovados a cada 15 minutos
        $schedule->job(new \App\Jobs\RefreshIfoodTokensJob(15))
            ->everyFifteenMinutes()
            ->name('refresh-ifood-tokens')
            ->withoutOverlapping();

        // Ativa pedidos de venda agendados que chegaram ao horário
        $schedule->command('orders:process-scheduled')
            ->everyMinute()
            ->name('process-scheduled-orders')
            ->withoutOverlapping();

        // ===== Subscription v2 Jobs =====

        // Processa regras de dunning (D0/D3/D5/D7/D30/D90/D120) às 01:00
        $schedule->job(new \App\Jobs\ProcessDunningRules())
            ->dailyAt('01:00')
            ->name('process-dunning-rules')
            ->withoutOverlapping();

        // Aplica downgrades agendados que chegaram ao vencimento às 01:30
        $schedule->job(new \App\Jobs\ApplyScheduledDowngrades())
            ->dailyAt('01:30')
            ->name('apply-scheduled-downgrades')
            ->withoutOverlapping();

        // Purga dados de tenants LGPD D120 — semanal às 03:00 nas segundas
        $schedule->job(new \App\Jobs\PurgeExpiredTenantData())
            ->weeklyOn(1, '03:00')
            ->name('purge-expired-tenant-data')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
