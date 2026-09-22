<?php

namespace App\Jobs;

use App\Models\SaleOrder;
use App\Services\EvolutionApiService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Envia o pedido do cardápio digital para o WhatsApp do próprio restaurante
 * (tenant->phone) via Evolution API — a mesma mensagem que o link wa.me do
 * checkout monta, só que sem depender do cliente clicar.
 */
class SendRestaurantOrderWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;
    public array $backoff = [30, 60, 120];

    public function __construct(private readonly SaleOrder $saleOrder)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(EvolutionApiService $evolutionApi, WhatsAppService $whatsAppService): void
    {
        $order  = $this->saleOrder->loadMissing(['tenant.plan', 'client', 'items.product']);
        $tenant = $order->tenant;

        $instance = $tenant?->evolution_instance;
        $phone    = $tenant?->phone;

        if (!$tenant?->plan?->has_whatsapp_notifications || !$instance || !$phone || !$order->client) {
            Log::info('SendRestaurantOrderWhatsApp: skip — WhatsApp do restaurante não configurado', [
                'sale_order_id' => $order->id,
                'has_plan'      => (bool) $tenant?->plan?->has_whatsapp_notifications,
                'has_instance'  => (bool) $instance,
                'has_phone'     => (bool) $phone,
            ]);
            return;
        }

        $message = $whatsAppService->generateSaleOrderMessage($order, $order->client, $tenant);

        // ponytail: retry após timeout com a mensagem já aceita pela Evolution pode duplicar; guardar message id se isso aparecer
        if (!$evolutionApi->sendText($instance, $phone, $message, linkPreview: false)) {
            throw new \RuntimeException("Evolution API retornou erro para o pedido #{$order->identify}");
        }

        Log::info('SendRestaurantOrderWhatsApp: pedido enviado ao restaurante', [
            'sale_order_id' => $order->id,
            'identify'      => $order->identify,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendRestaurantOrderWhatsApp: job falhou definitivamente', [
            'sale_order_id' => $this->saleOrder->id,
            'error'         => $exception->getMessage(),
        ]);
    }
}
