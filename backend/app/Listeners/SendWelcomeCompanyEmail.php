<?php

namespace App\Listeners;

use App\Events\CompanyRegistered;
use App\Mail\WelcomeCompanyMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Listener que envia e-mail de boas-vindas quando uma empresa se cadastra
 */
class SendWelcomeCompanyEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Número de tentativas em caso de falha
     */
    public $tries = 3;

    /**
     * Timeout em segundos
     */
    public $timeout = 60;

    /**
     * Handle the event.
     */
    public function handle(CompanyRegistered $event): void
    {
        try {
            $tenant = $event->tenant;
            $user = $event->user;
            $plan = $event->plan;

            // Verificar se o tenant tem e-mail configurado
            $emailTo = $tenant->email ?? $user->email;

            if (!$emailTo) {
                Log::warning('SendWelcomeCompanyEmail: Nenhum e-mail disponível para envio', [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Enviar e-mail de boas-vindas
            Mail::to($emailTo)->send(new WelcomeCompanyMail($tenant, $user, $plan));

            Log::info('SendWelcomeCompanyEmail: E-mail de boas-vindas enviado com sucesso', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'email_to' => $emailTo,
            ]);
        } catch (\Exception $e) {
            Log::error('SendWelcomeCompanyEmail: Erro ao enviar e-mail de boas-vindas', [
                'tenant_id' => $event->tenant->id ?? null,
                'user_id' => $event->user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lançar exceção para que o job seja marcado como falhado
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(CompanyRegistered $event, \Throwable $exception): void
    {
        Log::error('SendWelcomeCompanyEmail: Job falhou após todas as tentativas', [
            'tenant_id' => $event->tenant->id ?? null,
            'user_id' => $event->user->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}






