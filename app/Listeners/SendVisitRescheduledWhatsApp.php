<?php

namespace App\Listeners;

use App\Events\VisitRescheduled;
use App\Jobs\SendVisitWhatsAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendVisitRescheduledWhatsApp implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(VisitRescheduled $event): void
    {
        try {
            SendVisitWhatsAppNotification::dispatch($event->visit, 'rescheduled', $event->originalVisit);
        } catch (\Throwable $e) {
            Log::error('SendVisitRescheduledWhatsApp: falha ao despachar job', [
                'visit_id' => $event->visit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
