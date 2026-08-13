<?php

namespace App\Listeners;

use App\Events\VisitScheduled;
use App\Jobs\SendVisitWhatsAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendVisitScheduledWhatsApp implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(VisitScheduled $event): void
    {
        try {
            SendVisitWhatsAppNotification::dispatch($event->visit, 'scheduled');
        } catch (\Throwable $e) {
            Log::error('SendVisitScheduledWhatsApp: falha ao despachar job', [
                'visit_id' => $event->visit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
