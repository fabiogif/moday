<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Services\DeliveryLinkWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendShipmentDispatchedWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly Shipment $shipment,
        private readonly string $frontendBaseUrl,
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(DeliveryLinkWhatsAppService $deliveryLinkWhatsApp): void
    {
        try {
            $deliveryLinkWhatsApp->send($this->shipment, $this->frontendBaseUrl);
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendShipmentDispatchedWhatsApp: job falhou definitivamente', [
            'shipment_id' => $this->shipment->id,
            'error'       => $exception->getMessage(),
        ]);
    }
}
