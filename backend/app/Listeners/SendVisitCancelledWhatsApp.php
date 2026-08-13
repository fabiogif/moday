<?php

namespace App\Listeners;

use App\Events\VisitCancelled;
use App\Jobs\SendVisitWhatsAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendVisitCancelledWhatsApp implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(VisitCancelled $event): void
    {
        try {
            SendVisitWhatsAppNotification::dispatch($event->visit, 'cancelled');
        } catch (\Throwable $e) {
            Log::error('SendVisitCancelledWhatsApp: falha ao despachar job', [
                'visit_id' => $event->visit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
