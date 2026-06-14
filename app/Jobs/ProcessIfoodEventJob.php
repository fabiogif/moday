<?php

namespace App\Jobs;

use App\Services\Integrations\Ifood\IfoodEventService;
use App\Services\Integrations\Ifood\IfoodOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ProcessIfoodEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $eventId
    ) {
    }

    public function handle(
        IfoodEventService $eventService,
        IfoodOrderService $orderService
    ): void {
        $event = $eventService->find($this->eventId);

        if (!$event || $event->status !== 'pending') {
            return;
        }

        try {
            $payload = $event->payload ?? [];
            $orderPayload = Arr::get($payload, 'order', $payload);

            if (!is_array($orderPayload)) {
                $eventService->markSkipped($event, 'Payload sem dados do pedido.');
                return;
            }

            $orderService->ingestOrder($orderPayload, $event->tenant_id);

            $eventService->markProcessed($event);
        } catch (\Throwable $exception) {
            $eventService->markFailed($event, $exception->getMessage());

            Log::channel('ifood')->error('Falha ao processar evento iFood', [
                'event_id' => $event->event_id,
                'tenant_id' => $event->tenant_id,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}


