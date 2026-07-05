<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\Log;

class DeliveryLinkWhatsAppService
{
    public function __construct(private readonly EvolutionApiService $evolutionApi) {}

    /**
     * Envia o link de entrega (texto + preview) para o motorista via Evolution API.
     *
     * @throws \RuntimeException quando pre-requisitos nao sao atendidos ou o envio falha
     */
    public function send(Shipment $shipment, string $frontendBaseUrl): void
    {
        $shipment->loadMissing(['tenant.plan', 'driver', 'saleOrders.client']);

        $plan = $shipment->tenant?->plan;
        if (!$plan || !$plan->has_whatsapp_notifications) {
            throw new \RuntimeException('Plano não inclui notificações WhatsApp.');
        }

        $instance = $shipment->tenant?->evolution_instance;
        $phone    = $shipment->driver?->phone;

        if (!$instance) {
            throw new \RuntimeException('Instância WhatsApp não configurada para esta empresa.');
        }

        if (!$phone) {
            throw new \RuntimeException('Motorista sem telefone cadastrado.');
        }

        if (!$shipment->delivery_token) {
            throw new \RuntimeException('Romaneio sem link de entrega. Expedite o romaneio primeiro.');
        }

        $message = $this->buildMessage($shipment, $frontendBaseUrl);
        $number  = WhatsAppTemplateService::formatPhoneNumber($phone);

        $sent = $this->evolutionApi->sendText($instance, $number, $message, linkPreview: true);

        if (!$sent) {
            throw new \RuntimeException(
                "Falha ao enviar link de entrega do romaneio {$shipment->identify} para o motorista."
            );
        }

        Log::info('DeliveryLinkWhatsAppService: link enviado', [
            'shipment_id' => $shipment->id,
            'identify'    => $shipment->identify,
            'number'      => $number,
        ]);
    }

    public function buildMessage(Shipment $shipment, string $frontendBaseUrl): string
    {
        $s = $shipment;
        $deliveryUrl = rtrim($frontendBaseUrl, '/') . '/delivery/' . $s->delivery_token;

        $stopCount = $s->saleOrders->count();
        $km = $s->estimated_km ? number_format((float) $s->estimated_km, 1, ',', '.') . ' km' : '\u{2014}';
        $duration = $s->estimated_duration_minutes
            ? ($s->estimated_duration_minutes >= 60
                ? floor($s->estimated_duration_minutes / 60) . 'h' . str_pad($s->estimated_duration_minutes % 60, 2, '0', STR_PAD_LEFT) . 'min'
                : $s->estimated_duration_minutes . ' min')
            : '\u{2014}';

        $stops = $s->saleOrders->map(function ($order, $index) {
            $client = $order->client?->company_name
                ?? $order->client?->trade_name
                ?? $order->client?->name
                ?? 'Sem cliente';
            $city = $order->shipping_city ? " \u{2014} {$order->shipping_city}" : '';
            return ($index + 1) . ". {$order->identify} \u{00B7} {$client}{$city}";
        })->join("\n");

        $lines = [
            "\u{1F69A} *Romaneio Expedido \u{2014} {$s->identify}*",
            "",
        ];

        if ($s->route_name) {
            $lines[] = "\u{1F4CD} *Rota:* {$s->route_name}";
        }
        if ($s->vehicle_plate) {
            $lines[] = "\u{1F697} *Ve\u{00ED}culo:* {$s->vehicle_plate}";
        }

        $lines[] = "\u{1F4E6} *Entregas:* {$stopCount}";
        $lines[] = "\u{1F4CF} *Dist\u{00E2}ncia:* {$km}";
        $lines[] = "\u{23F1}\u{FE0F} *Tempo estimado:* {$duration}";
        $lines[] = "";
        $lines[] = "*Paradas:*";
        $lines[] = $stops;
        $lines[] = "";
        $lines[] = "\u{1F517} *Link de entrega:*";
        $lines[] = $deliveryUrl;
        $lines[] = "";
        $lines[] = "Use o link acima para registrar as entregas e comprovantes. Boa viagem!";

        return implode("\n", $lines);
    }
}
