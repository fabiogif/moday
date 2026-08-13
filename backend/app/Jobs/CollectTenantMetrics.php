<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantMetrics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CollectTenantMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutos

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando coleta de métricas diárias de tenants');

        $tenants = Tenant::whereIn('account_status', ['active', 'trial'])->get();
        $collected = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            try {
                TenantMetrics::recordDaily($tenant);
                $collected++;
            } catch (\Exception $e) {
                $errors++;
                Log::error("Erro ao coletar métricas do tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        Log::info("Coleta de métricas concluída. Coletadas: {$collected}, Erros: {$errors}");
    }
}

