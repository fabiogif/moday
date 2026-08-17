<?php

namespace App\Services;

use App\Mail\OrderCompletedMail;
use App\Mail\OrderReceiptMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderEmailService
{
    public function sendOrderCompletedEmail(Order $order): bool
    {
        return $this->sendToClient($order, 'completed', function (Order $loaded, string $email, string $name) {
            Mail::to($email, $name)->send(new OrderCompletedMail($loaded));
        });
    }

    public function sendOrderReceiptEmail(Order $order): bool
    {
        return $this->sendToClient($order, 'receipt', function (Order $loaded, string $email, string $name) {
            Mail::to($email, $name)->send(new OrderReceiptMail($loaded));
        });
    }

    private function sendToClient(Order $order, string $type, callable $sender): bool
    {
        $order = $this->loadOrderForEmail($order);

        $email = $order->client?->email;
        if (!$email) {
            Log::warning('OrderEmailService: Cliente sem e-mail', [
                'order_id' => $order->id,
                'identify' => $order->identify,
                'type' => $type,
            ]);

            return false;
        }

        try {
            $sender($order, $email, $order->client->name ?? 'Cliente');

            Log::info('OrderEmailService: E-mail enviado', [
                'order_id' => $order->id,
                'identify' => $order->identify,
                'type' => $type,
                'email' => $email,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('OrderEmailService: Falha ao enviar e-mail', [
                'order_id' => $order->id,
                'identify' => $order->identify,
                'type' => $type,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function loadOrderForEmail(Order $order): Order
    {
        return $order->loadMissing(['client', 'tenant', 'products', 'paymentMethod']);
    }
}
