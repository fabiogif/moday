<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Número máximo de tentativas antes de ir para failed_jobs */
    public int $tries = 3;

    /** @var int Timeout em segundos por tentativa */
    public int $timeout = 90;

    /** @var array Backoff progressivo entre retries (em segundos) */
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly Order  $order,
        private readonly string $eventType,
        private readonly string $oldStatus = '',
        private readonly string $newStatus = '',
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(EvolutionApiService $evolutionApi): void
    {
        $this->order->loadMissing(['client', 'products', 'tenant', 'paymentMethod', 'orderStatus']);

        if ($this->order->whatsapp_notifications === false) {
            Log::info('SendWhatsAppNotification: skip — cliente optou por não receber no WhatsApp', [
                'order_id' => $this->order->id,
            ]);
            return;
        }

        $instance = $this->order->tenant?->evolution_instance;
        // Número digitado neste pedido; sem ele, o WhatsApp/telefone do cadastro
        $phone    = $this->order->customer_phone
                 ?: ($this->order->client?->whatsapp ?? $this->order->client?->phone);

        if (!$instance || !$phone) {
            Log::info('SendWhatsAppNotification: skip — instância ou telefone não configurado', [
                'order_id'     => $this->order->id,
                'has_instance' => (bool) $instance,
                'has_phone'    => (bool) $phone,
            ]);
            return;
        }

        $message = $this->buildMessage();

        $sent = $evolutionApi->sendText($instance, $phone, $message);

        if (!$sent) {
            // Forçar retry: lança exceção para que o worker registre a falha
            throw new \RuntimeException(
                "Evolution API retornou erro para o pedido #{$this->order->identify}"
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsAppNotification: job falhou definitivamente após todas as tentativas', [
            'order_id'   => $this->order->id,
            'event_type' => $this->eventType,
            'error'      => $exception->getMessage(),
        ]);
    }

    private function buildMessage(): string
    {
        $order     = $this->order;
        $pagamento = $order->paymentMethod?->name ?? $order->payment_method ?? 'Não informado';
        $endereco  = $order->is_delivery
            ? ($order->full_delivery_address ?: 'Não informado')
            : 'Retirada no local';

        $itens = $order->products
            ->map(fn ($p) =>
                "• {$p->title} x{$p->pivot->qty} — R$ " .
                number_format((float) $p->pivot->price * (int) $p->pivot->qty, 2, ',', '.')
            )
            ->join("\n");

        $total = 'R$ ' . number_format((float) $order->total, 2, ',', '.');

        $restaurante = $order->tenant?->name ?? 'Restaurante';
        $contato     = $order->tenant?->phone ? "📞 *{$restaurante}:* {$order->tenant->phone}" : null;

        if ($this->eventType === 'new_order') {
            return implode("\n", [
                "✅ *Pedido Recebido — #{$order->identify}*",
                "",
                "Olá, " . ($order->contactName() ?? 'Cliente') . "! Seu pedido foi recebido.",
                "",
                "📦 *Itens:*",
                $itens,
                "",
                "💰 *Total:* {$total}",
                "💳 *Pagamento:* {$pagamento}",
                "📍 *Entrega:* {$endereco}",
                "🔄 *Status:* {$order->status}",
                ...($contato ? ["", $contato] : []),
                "",
                "Muito obrigado pelo seu pedido! 🙏 Esperamos que você goste.",
            ]);
        }

        return implode("\n", [
            "🔔 *Atualização do Pedido #{$order->identify}*",
            "",
            "Olá, " . ($order->contactName() ?? 'Cliente') . "!",
            "",
            "📦 *Itens:*",
            $itens,
            "",
            "💰 *Total:* {$total}",
            "💳 *Pagamento:* {$pagamento}",
            "📍 *Entrega:* {$endereco}",
            "↩️ *Status anterior:* {$this->oldStatus}",
            "✅ *Status atual:* {$this->newStatus}",
            ...($contato ? ["", $contato] : []),
        ]);
    }
}
