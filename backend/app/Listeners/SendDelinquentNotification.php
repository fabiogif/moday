<?php

namespace App\Listeners;

use App\Events\SubscriptionDelinquent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDelinquentNotification implements ShouldQueue
{
    public function handle(SubscriptionDelinquent $event): void
    {
        $tenant = $event->tenant;

        if (!$tenant->email) {
            return;
        }

        try {
            Mail::raw(
                "Olá, {$tenant->name}!\n\nSeu acesso ao DistribTec foi bloqueado por inadimplência (7 dias sem pagamento).\n\nPara recuperar o acesso, acesse o painel e atualize seu método de pagamento.\n\nSe já regularizou o pagamento, aguarde até 24h para a atualização automática.",
                fn ($m) => $m->to($tenant->email)->subject('Acesso bloqueado - Regularize seu pagamento')
            );
        } catch (\Throwable $e) {
            Log::error('SendDelinquentNotification: falha no email', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
