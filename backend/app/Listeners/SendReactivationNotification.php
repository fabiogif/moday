<?php

namespace App\Listeners;

use App\Events\SubscriptionReactivated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReactivationNotification implements ShouldQueue
{
    public function handle(SubscriptionReactivated $event): void
    {
        $tenant = $event->tenant;
        $plan   = $event->plan;

        if (!$tenant->email) {
            return;
        }

        try {
            Mail::raw(
                "Olá, {$tenant->name}!\n\nSua assinatura do plano {$plan->name} foi reativada com sucesso.\n\nBem-vindo de volta ao DistribTec! Seu acesso completo foi restaurado.",
                fn ($m) => $m->to($tenant->email)->subject('Assinatura reativada com sucesso!')
            );
        } catch (\Throwable $e) {
            Log::error('SendReactivationNotification: falha no email', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
