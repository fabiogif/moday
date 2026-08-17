<?php

namespace App\Mail;

use App\Mail\Concerns\BuildsOrderEmailData;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceiptMail extends Mailable
{
    use BuildsOrderEmailData, Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->order->tenant?->name ?? config('app.name');

        return new Envelope(
            subject: "Recibo do pedido #{$this->order->identify} — {$tenantName}",
            replyTo: [
                new Address(config('mail.support_to'), $tenantName),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-receipt',
            with: $this->buildOrderEmailViewData($this->order),
        );
    }
}
