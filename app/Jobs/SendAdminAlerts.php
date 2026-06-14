<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantBilling;
use App\Models\AdminUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 120;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando envio de alertas administrativos');

        $alerts = $this->collectAlerts();

        if (empty($alerts)) {
            Log::info('Nenhum alerta para enviar');
            return;
        }

        // Busca super admins para receber alertas
        $admins = AdminUser::superAdmins()->active()->get();

        if ($admins->isEmpty()) {
            Log::warning('Nenhum super admin ativo para receber alertas');
            return;
        }

        // TODO: Implementar envio de email quando sistema de email estiver pronto
        foreach ($admins as $admin) {
            try {
                // Mail::to($admin->email)->send(new AdminAlertsEmail($alerts));
                Log::info("Alertas enviados para {$admin->email}", ['alerts_count' => count($alerts)]);
            } catch (\Exception $e) {
                Log::error("Erro ao enviar alertas para {$admin->email}: " . $e->getMessage());
            }
        }
    }

    /**
     * Coleta todos os alertas relevantes
     */
    private function collectAlerts(): array
    {
        $alerts = [];

        // 1. Trials expirando
        $trialsExpiring = Tenant::where('account_status', 'trial')
            ->whereBetween('trial_expires_at', [now(), now()->addDays(3)])
            ->count();

        if ($trialsExpiring > 0) {
            $alerts[] = [
                'type' => 'warning',
                'priority' => 'medium',
                'title' => 'Trials Expirando',
                'message' => "{$trialsExpiring} empresa(s) com trial expirando nos próximos 3 dias",
            ];
        }

        // 2. Faturas vencidas
        $overdueInvoices = TenantBilling::overdue()->count();
        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'error',
                'priority' => 'high',
                'title' => 'Faturas Vencidas',
                'message' => "{$overdueInvoices} fatura(s) vencida(s)",
            ];
        }

        // 3. Empresas inativas
        $inactiveTenants = Tenant::whereIn('account_status', ['active', 'trial'])
            ->where(function($q) {
                $q->where('last_login_at', '<', now()->subDays(14))
                  ->orWhereNull('last_login_at');
            })
            ->count();

        if ($inactiveTenants > 10) {
            $alerts[] = [
                'type' => 'warning',
                'priority' => 'low',
                'title' => 'Empresas Inativas',
                'message' => "{$inactiveTenants} empresa(s) sem acesso há mais de 14 dias",
            ];
        }

        // 4. Empresas ultrapassando limites
        $overLimit = Tenant::whereRaw('messages_sent >= messages_limit')
            ->where('messages_limit', '>', 0)
            ->count();

        if ($overLimit > 0) {
            $alerts[] = [
                'type' => 'error',
                'priority' => 'high',
                'title' => 'Limites Ultrapassados',
                'message' => "{$overLimit} empresa(s) ultrapassou o limite de mensagens",
            ];
        }

        return $alerts;
    }
}

