<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendWhatsAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderCreatedWhatsApp implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderCreated $event): void
    {
        try {
            SendWhatsAppNotification::dispatch($event->order, 'new_order');

            Log::info('SendOrderCreatedWhatsApp: job despachado', [
                'order_id' => $event->order->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendOrderCreatedWhatsApp: falha ao despachar job', [
                'order_id' => $event->order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
