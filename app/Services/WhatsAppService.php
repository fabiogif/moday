<?php

namespace App\Services;

class WhatsAppService
{
    public function generateOrderMessage(\App\Models\Order $order, \App\Models\Client $client, \App\Models\Tenant $tenant): string
    {
        $products = $order->products->map(function ($product) {
            $qty = $product->pivot->qty ?? $product->pivot->quantity ?? 1;
            $price = $product->pivot->price ?? $product->price ?? 0;

            \Log::info('WhatsApp: Produto no pedido', [
                'product_id' => $product->id,
                'name' => $product->name,
                'pivot_price' => $product->pivot->price ?? 'null',
                'product_price' => $product->price ?? 'null',
                'final_price' => $price,
                'qty' => $qty,
            ]);

            $priceFormatted = number_format((float) $price, 2, ',', '.');
            $subtotal = $qty * (float) $price;
            $subtotalFormatted = number_format($subtotal, 2, ',', '.');

            return "• {$qty}x {$product->name} - R$ {$priceFormatted} (Subtotal: R$ {$subtotalFormatted})";
        })->implode("\n");

        $total = number_format((float) $order->total, 2, ',', '.');

        $message = "*Novo Pedido #{$order->identify}*\n\n";
        $message .= "*Cliente:* {$client->name}\n";
        $message .= "*Telefone:* {$client->phone}\n";
        $message .= "*Email:* {$client->email}\n\n";
        $message .= "*Produtos:*\n{$products}\n\n";
        $message .= "*Total:* R$ {$total}\n\n";

        if ($order->is_delivery) {
            $message .= $this->formatDeliveryAddress($order);
        } else {
            $message .= "*Retirada no Local*\n\n";
        }

        $paymentMethodName = $order->paymentMethod?->name
            ?? $order->payment_method
            ?? 'Não informado';

        $message .= '*Forma de Pagamento:* ' . $this->translatePaymentMethod($paymentMethodName);

        return $message;
    }

    public function generateLink(?string $phone, string $message): ?string
    {
        \Log::info('WhatsAppService::generateLink - Phone received:', ['phone' => $phone]);

        if (!$phone) {
            \Log::warning('WhatsAppService::generateLink - No phone provided');
            return null;
        }

        $whatsapp = preg_replace('/[^0-9]/', '', $phone);
        \Log::info('WhatsAppService::generateLink - Cleaned phone:', ['whatsapp' => $whatsapp]);

        if (strlen($whatsapp) === 11 || strlen($whatsapp) === 10) {
            $whatsapp = '55' . $whatsapp;
        }

        $encodedMessage = urlencode($message);
        $link = "https://wa.me/{$whatsapp}?text={$encodedMessage}";

        \Log::info('WhatsAppService::generateLink - Generated link:', ['link' => $link]);

        return $link;
    }

    private function formatDeliveryAddress(\App\Models\Order $order): string
    {
        $address = "{$order->delivery_address}, {$order->delivery_number}";
        if ($order->delivery_complement) {
            $address .= " - {$order->delivery_complement}";
        }
        $address .= "\n{$order->delivery_neighborhood}, {$order->delivery_city}/{$order->delivery_state}";
        $address .= "\nCEP: {$order->delivery_zip_code}";

        $message = "*Endereço de Entrega:*\n{$address}\n\n";

        if ($order->delivery_notes) {
            $message .= "*Observações:* {$order->delivery_notes}\n\n";
        }

        return $message;
    }

    private function translatePaymentMethod(?string $method): string
    {
        if (!$method) {
            return 'Não informado';
        }

        $methods = [
            'pix' => 'PIX',
            'credit_card' => 'Cartão de Crédito',
            'debit_card' => 'Cartão de Débito',
            'money' => 'Dinheiro',
            'bank_transfer' => 'Transferência Bancária',
        ];

        return $methods[strtolower($method)] ?? $method;
    }
}
