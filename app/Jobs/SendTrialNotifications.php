<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Notifications\TrialExpiringIn3Days;
use App\Notifications\TrialExpiringTomorrow;
use App\Notifications\TrialExpiredToday;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendTrialNotifications implements ShouldQueue
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
        Log::info('SendTrialNotifications job iniciado');

        try {
            // Notificações para trials que expiram em 3 dias
            $this->sendExpiringIn3DaysNotifications();

            // Notificações para trials que expiram amanhã (1 dia)
            $this->sendExpiringTomorrowNotifications();

            // Notificações para trials que expiram hoje
            $this->sendExpiringTodayNotifications();

            Log::info('SendTrialNotifications job concluído');

        } catch (\Exception $e) {
            Log::error('Erro no job SendTrialNotifications: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envia notificações para trials que expiram em 3 dias
     */
    private function sendExpiringIn3DaysNotifications(): void
    {
        $expiringIn3Days = Tenant::where('account_status', 'trial')
            ->whereNotNull('trial_expires_at')
            ->whereBetween('trial_expires_at', [
                now()->addDays(3)->startOfDay(),
                now()->addDays(3)->endOfDay()
            ])
            ->get();

        Log::info("Encontrados {$expiringIn3Days->count()} trials expirando em 3 dias");

        foreach ($expiringIn3Days as $tenant) {
            try {
                Log::info("Enviando notificação de 3 dias para tenant {$tenant->id}");
                
                $users = $tenant->users()->where('is_active', true)->get();
                Notification::send($users, new TrialExpiringIn3Days($tenant));
                
            } catch (\Exception $e) {
                Log::error("Erro ao enviar notificação para tenant {$tenant->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Envia notificações para trials que expiram amanhã
     */
    private function sendExpiringTomorrowNotifications(): void
    {
        $expiringTomorrow = Tenant::where('account_status', 'trial')
            ->whereNotNull('trial_expires_at')
            ->whereBetween('trial_expires_at', [
                now()->addDay()->startOfDay(),
                now()->addDay()->endOfDay()
            ])
            ->get();

        Log::info("Encontrados {$expiringTomorrow->count()} trials expirando amanhã");

        foreach ($expiringTomorrow as $tenant) {
            try {
                Log::info("Enviando notificação de 1 dia para tenant {$tenant->id}");
                
                $users = $tenant->users()->where('is_active', true)->get();
                Notification::send($users, new TrialExpiringTomorrow($tenant));
                
            } catch (\Exception $e) {
                Log::error("Erro ao enviar notificação para tenant {$tenant->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Envia notificações para trials que expiram hoje
     */
    private function sendExpiringTodayNotifications(): void
    {
        $expiringToday = Tenant::where('account_status', 'trial')
            ->whereNotNull('trial_expires_at')
            ->whereBetween('trial_expires_at', [
                now()->startOfDay(),
                now()->endOfDay()
            ])
            ->get();

        Log::info("Encontrados {$expiringToday->count()} trials expirando hoje");

        foreach ($expiringToday as $tenant) {
            try {
                Log::info("Enviando notificação do último dia para tenant {$tenant->id}");
                
                $users = $tenant->users()->where('is_active', true)->get();
                Notification::send($users, new TrialExpiredToday($tenant));
                
            } catch (\Exception $e) {
                Log::error("Erro ao enviar notificação para tenant {$tenant->id}: {$e->getMessage()}");
            }
        }
    }
}

