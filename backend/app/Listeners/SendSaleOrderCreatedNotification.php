<?php

namespace App\Listeners;

use App\Events\SaleOrderCreated;
use App\Models\SaleOrder;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSaleOrderCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(SaleOrderCreated $event): void
    {
        try {
            $order = $event->saleOrder;

            if (!$order->relationLoaded('client')) {
                $order->load('client');
            }

            $clientName = $order->client?->trade_name
                ?? $order->client?->company_name
                ?? $order->client?->name
                ?? $order->client?->contact_name
                ?? 'Cliente';

            $users = User::where('tenant_id', $order->tenant_id)
                ->where('is_active', true)
                ->get();

            foreach ($users as $user) {
                $this->notificationService->send(
                    $user->id,
                    $order->tenant_id,
                    'order_created',
                    [
                        'title' => 'Novo Pedido de Venda',
                        'message' => "Pedido #{$order->identify} de {$clientName} — R$ " .
                            number_format((float) $order->total, 2, ',', '.'),
                        'order_id' => $order->identify,
                        'order_total' => $order->total,
                        'client_name' => $clientName,
                        'source' => 'sale_order',
                        'href' => '/sale-orders/' . $order->id,
                        'notifiable_type' => SaleOrder::class,
                        'notifiable_id' => $order->id,
                    ]
                );
            }

            Log::info('SendSaleOrderCreatedNotification: Notifications sent', [
                'sale_order_id' => $order->id,
                'notified_users' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('SendSaleOrderCreatedNotification: Error', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
