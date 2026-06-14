<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\SubscriptionDomainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyScheduledDowngrades implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(SubscriptionDomainService $domain): void
    {
        Log::info('ApplyScheduledDowngrades: iniciando');

        $tenants = Tenant::whereNotNull('scheduled_downgrade_plan_id')
            ->whereNotNull('scheduled_downgrade_at')
            ->where('scheduled_downgrade_at', '<=', now())
            ->get();

        $applied = 0;
        $failed  = 0;

        foreach ($tenants as $tenant) {
            try {
                $success = $domain->applyScheduledDowngrade($tenant);
                $success ? $applied++ : $failed++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('ApplyScheduledDowngrades: erro ao aplicar downgrade', [
                    'tenant_id' => $tenant->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info("ApplyScheduledDowngrades: concluído. Aplicados: {$applied}, Falhas: {$failed}.");
    }
}
