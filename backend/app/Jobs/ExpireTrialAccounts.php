<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Notifications\TrialExpiredToday;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ExpireTrialAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ExpireTrialAccounts job iniciado');

        try {
            // Busca tenants com trial expirado apenas em planos pagos
            $expiredTrials = Tenant::with('plan')
                ->where('account_status', 'trial')
                ->where('trial_expires_at', '<=', now())
                ->whereNotNull('trial_expires_at')
                ->whereHas('plan', function ($query) {
                    $query->where('price', '>', 0);
                })
                ->get();

            $count = $expiredTrials->count();
            Log::info("Encontrados {$count} trials expirados");

            foreach ($expiredTrials as $tenant) {
                try {
                    Log::info("Expirando trial do tenant {$tenant->id} - {$tenant->name}");
                    
                    $tenant->expireTrial();
                    
                    // Envia notificação para todos os usuários do tenant
                    $users = $tenant->users()->where('is_active', true)->get();
                    Notification::send($users, new TrialExpiredToday($tenant));

                    Log::info("Trial expirado com sucesso para tenant {$tenant->id}");

                } catch (\Exception $e) {
                    Log::error("Erro ao expirar trial do tenant {$tenant->id}: {$e->getMessage()}");
                }
            }

            Log::info("ExpireTrialAccounts job concluído. {$count} trials expirados.");

        } catch (\Exception $e) {
            Log::error('Erro no job ExpireTrialAccounts: ' . $e->getMessage());
            throw $e;
        }
    }
}

