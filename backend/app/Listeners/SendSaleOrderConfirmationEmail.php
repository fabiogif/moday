<?php

namespace App\Listeners;

use App\Events\SaleOrderConfirmedEvent;
use App\Mail\SaleOrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendSaleOrderConfirmationEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(SaleOrderConfirmedEvent $event): void
    {
        $order  = $event->order->load('client');
        $client = $order->client;

        if (!$client) {
            return;
        }

        $email = $client->contact_email ?: $client->email;
        if (empty($email)) {
            return;
        }

        Mail::to($email)->queue(new SaleOrderConfirmationMail($order));
    }
}
