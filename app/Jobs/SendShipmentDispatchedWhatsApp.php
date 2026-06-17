<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Services\EvolutionApiService;
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

    public function handle(EvolutionApiService $evolutionApi): void
    {
        $this->shipment->loadMissing(['tenant.plan', 'driver', 'saleOrders.client']);

        $plan = $this->shipment->tenant?->plan;
        if (!$plan || !$plan->has_whatsapp_notifications) {
            Log::info('SendShipmentDispatchedWhatsApp: skip — plano não inclui WhatsApp', [
                'shipment_id' => $this->shipment->id,
                'plan'        => $plan?->name,
            ]);
            return;
        }

        $instance = $this->shipment->tenant?->evolution_instance;
        $phone = $this->shipment->driver?->phone;

        if (!$instance || !$phone) {
            Log::info('SendShipmentDispatchedWhatsApp: skip — instância ou telefone do motorista ausente', [
                'shipment_id'  => $this->shipment->id,
                'has_instance' => (bool) $instance,
                'has_phone'    => (bool) $phone,
            ]);
            return;
        }

        $message = $this->buildMessage();

        $sent = $evolutionApi->sendText($instance, $phone, $message);

        if (!$sent) {
            throw new \RuntimeException(
                "Evolution API falhou ao enviar romaneio {$this->shipment->identify} para o motorista"
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendShipmentDispatchedWhatsApp: job falhou definitivamente', [
            'shipment_id' => $this->shipment->id,
            'error'       => $exception->getMessage(),
        ]);
    }

    private function buildMessage(): string
    {
        $s = $this->shipment;
        $deliveryUrl = rtrim($this->frontendBaseUrl, '/') . '/delivery/' . $s->delivery_token;

        $stopCount = $s->saleOrders->count();
        $km = $s->estimated_km ? number_format((float) $s->estimated_km, 1, ',', '.') . ' km' : '—';
        $duration = $s->estimated_duration_minutes
            ? ($s->estimated_duration_minutes >= 60
                ? floor($s->estimated_duration_minutes / 60) . 'h' . str_pad($s->estimated_duration_minutes % 60, 2, '0', STR_PAD_LEFT) . 'min'
                : $s->estimated_duration_minutes . ' min')
            : '—';

        $stops = $s->saleOrders->map(function ($order, $index) {
            $client = $order->client?->company_name
                ?? $order->client?->trade_name
                ?? $order->client?->name
                ?? 'Sem cliente';
            $city = $order->shipping_city ? " — {$order->shipping_city}" : '';
            return ($index + 1) . ". {$order->identify} · {$client}{$city}";
        })->join("\n");

        $lines = [
            "🚚 *Romaneio Expedido — {$s->identify}*",
            "",
        ];

        if ($s->route_name) {
            $lines[] = "📍 *Rota:* {$s->route_name}";
        }
        if ($s->vehicle_plate) {
            $lines[] = "🚗 *Veículo:* {$s->vehicle_plate}";
        }

        $lines[] = "📦 *Entregas:* {$stopCount}";
        $lines[] = "📏 *Distância:* {$km}";
        $lines[] = "⏱️ *Tempo estimado:* {$duration}";
        $lines[] = "";
        $lines[] = "*Paradas:*";
        $lines[] = $stops;
        $lines[] = "";
        $lines[] = "🔗 *Link de entrega:*";
        $lines[] = $deliveryUrl;
        $lines[] = "";
        $lines[] = "Use o link acima para registrar as entregas e comprovantes. Boa viagem!";

        return implode("\n", $lines);
    }
}
