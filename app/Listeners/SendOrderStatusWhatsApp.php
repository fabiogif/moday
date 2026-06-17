<?php

namespace App\Listeners;

use App\Events\OrderStatusChangedEvent;
use App\Events\OrderStatusUpdated;
use App\Jobs\SendWhatsAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderStatusWhatsApp implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderStatusChangedEvent|OrderStatusUpdated $event): void
    {
        try {
            SendWhatsAppNotification::dispatch(
                $event->order,
                'status_change',
                $event->oldStatus,
                $event->newStatus,
            );

            Log::info('SendOrderStatusWhatsApp: job despachado', [
                'order_id'   => $event->order->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendOrderStatusWhatsApp: falha ao despachar job', [
                'order_id' => $event->order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
