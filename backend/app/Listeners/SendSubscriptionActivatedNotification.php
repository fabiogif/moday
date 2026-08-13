<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionActivatedNotification implements ShouldQueue
{
    public function handle(SubscriptionActivated $event): void
    {
        $tenant = $event->tenant;
        $plan   = $event->plan;

        if (!$tenant->email) {
            return;
        }

        try {
            Mail::raw(
                "Parabéns, {$tenant->name}!\n\nSua assinatura do plano {$plan->name} foi ativada com sucesso.\n\nBem-vindo ao DistribTec! Acesse seu painel para começar.",
                fn ($m) => $m->to($tenant->email)->subject("Assinatura {$plan->name} ativada!")
            );
        } catch (\Throwable $e) {
            Log::error('SendSubscriptionActivated: falha no email', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
