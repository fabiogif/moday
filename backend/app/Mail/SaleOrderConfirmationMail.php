<?php

namespace App\Mail;

use App\Models\SaleOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleOrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SaleOrder $order) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->order->tenant?->name ?? config('app.name');

        return new Envelope(
            subject: "Confirmação do pedido {$this->order->identify} — {$tenantName}",
            replyTo: [
                new Address(config('mail.support_to', config('mail.from.address')), $tenantName),
            ],
        );
    }

    public function content(): Content
    {
        $order      = $this->order->load(['client', 'items.product', 'tenant']);
        $tenantName = $order->tenant?->name ?? config('app.name');
        $clientName = $order->client?->trade_name ?? $order->client?->company_name ?? 'Cliente';

        $items = $order->items->map(fn ($i) => [
            'name'       => $i->product?->name ?? 'Produto',
            'quantity'   => $i->quantity,
            'unit_price' => $i->unit_price,
            'total'      => $i->subtotal,
            'item_type'  => $i->item_type ?? 'venda',
        ])->all();

        return new Content(
            view: 'emails.sale-order-confirmation',
            with: compact('order', 'tenantName', 'clientName', 'items'),
        );
    }
}
