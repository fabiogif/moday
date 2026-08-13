<?php

namespace App\Listeners;

use App\Events\PlanConfirmed;
use App\Mail\PlanConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Listener que envia e-mail de confirmação quando uma empresa confirma/migra plano
 */
class SendPlanConfirmationEmail implements ShouldQueue
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
    public function handle(PlanConfirmed $event): void
    {
        try {
            $tenant = $event->tenant;
            $plan = $event->plan;
            $oldPlan = $event->oldPlan;
            $migration = $event->migration;

            // Buscar e-mail do tenant ou do usuário administrador
            $emailTo = $tenant->email;

            if (!$emailTo) {
                // Tentar buscar e-mail do usuário administrador do tenant
                $adminUser = $tenant->users()->whereHas('profiles', function ($query) {
                    $query->where('name', 'Admin');
                })->first();

                if ($adminUser && $adminUser->email) {
                    $emailTo = $adminUser->email;
                }
            }

            if (!$emailTo) {
                Log::warning('SendPlanConfirmationEmail: Nenhum e-mail disponível para envio', [
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                ]);
                return;
            }

            // Enviar e-mail de confirmação
            Mail::to($emailTo)->send(
                new PlanConfirmationMail($tenant, $plan, $oldPlan, $migration)
            );

            Log::info('SendPlanConfirmationEmail: E-mail de confirmação de plano enviado com sucesso', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'old_plan_id' => $oldPlan?->id,
                'is_migration' => $oldPlan !== null,
                'email_to' => $emailTo,
            ]);
        } catch (\Exception $e) {
            Log::error('SendPlanConfirmationEmail: Erro ao enviar e-mail de confirmação', [
                'tenant_id' => $event->tenant->id ?? null,
                'plan_id' => $event->plan->id ?? null,
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
    public function failed(PlanConfirmed $event, \Throwable $exception): void
    {
        Log::error('SendPlanConfirmationEmail: Job falhou após todas as tentativas', [
            'tenant_id' => $event->tenant->id ?? null,
            'plan_id' => $event->plan->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}






