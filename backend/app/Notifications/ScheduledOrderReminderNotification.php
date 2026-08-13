<?php

namespace App\Notifications;

use App\Models\SaleOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduledOrderReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly SaleOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $clientName = $this->order->client?->trade_name ?? $this->order->client?->company_name ?? 'Cliente';

        return (new MailMessage)
            ->subject("Pedido agendado ativado: {$this->order->identify}")
            ->greeting("Pedido agendado ativado!")
            ->line("O pedido **{$this->order->identify}** para o cliente **{$clientName}** foi ativado automaticamente conforme agendamento.")
            ->line('Valor total: R$ ' . number_format((float) $this->order->total, 2, ',', '.'))
            ->action('Ver pedido', url('/sale-orders/' . $this->order->id))
            ->line('O pedido está agora com status **Aprovado** e aguarda separação.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'scheduled_order_activated',
            'sale_order_id' => $this->order->id,
            'identify'      => $this->order->identify,
            'total'         => $this->order->total,
            'message'       => "Pedido {$this->order->identify} foi ativado pelo agendamento",
        ];
    }
}
