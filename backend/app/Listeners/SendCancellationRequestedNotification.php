<?php

namespace App\Listeners;

use App\Events\SubscriptionCancellationRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCancellationRequestedNotification implements ShouldQueue
{
    public function handle(SubscriptionCancellationRequested $event): void
    {
        $tenant      = $event->tenant;
        $accessUntil = $event->accessUntil;

        if (!$tenant->email) {
            return;
        }

        $accessLine = $accessUntil
            ? "Você continuará tendo acesso ao sistema até {$accessUntil}."
            : 'Você continuará tendo acesso até o fim do ciclo atual.';

        try {
            Mail::raw(
                "Olá, {$tenant->name}!\n\nRecebemos sua solicitação de cancelamento da assinatura DistribTec.\n\n{$accessLine}\n\nSe mudar de ideia, você pode reativar sua assinatura a qualquer momento antes desta data.\n\nObrigado por usar o DistribTec.",
                fn ($m) => $m->to($tenant->email)->subject('Cancelamento de assinatura confirmado')
            );
        } catch (\Throwable $e) {
            Log::error('SendCancellationRequested: falha no email', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
