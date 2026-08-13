<?php

namespace App\Mail\Concerns;

use App\Models\Order;

trait BuildsOrderEmailData
{
    protected function buildOrderEmailViewData(Order $order): array
    {
        $order->loadMissing(['client', 'tenant', 'products', 'paymentMethod']);

        $items = $order->products->map(function ($product) {
            $qty = (int) ($product->pivot->qty ?? $product->pivot->quantity ?? 1);
            $unitPrice = (float) ($product->pivot->price ?? $product->price ?? 0);

            return [
                'name' => $product->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => $qty * $unitPrice,
            ];
        });

        $slug = $order->tenant?->slug ?? $order->tenant?->url;

        return [
            'order' => $order,
            'tenantName' => $order->tenant?->name ?? config('app.name'),
            'clientName' => $order->client?->name ?? 'Cliente',
            'items' => $items,
            'paymentMethod' => $order->paymentMethod?->name ?? $order->payment_method ?? 'Não informado',
            'trackUrl' => $slug
                ? rtrim(config('app.frontend_url', config('app.url')), '/').'/store/'.$slug.'/track?order='.$order->identify
                : null,
        ];
    }
}
