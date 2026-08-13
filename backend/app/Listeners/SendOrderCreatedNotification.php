<?php

namespace App\Listeners;

use App\Events\OrderCreatedEvent;
use App\Events\OrderCreated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the event - Suporta ambos OrderCreatedEvent e OrderCreated
     */
    public function handle(OrderCreatedEvent|OrderCreated $event): void
    {
        try {
            $order = $event->order;
            
            // Garantir que client está carregado
            if (!$order->relationLoaded('client')) {
                $order->load('client');
            }

            // Notificar todos os usuários do tenant
            $users = \App\Models\User::where('tenant_id', $order->tenant_id)
                ->where('is_active', true)
                ->get();

            foreach ($users as $user) {
                $this->notificationService->send(
                    $user->id,
                    $order->tenant_id,
                    'order_created',
                    [
                        'title' => 'Novo Pedido Recebido',
                        'message' => "Pedido #{$order->identify} no valor de R$ " . number_format($order->total, 2, ',', '.') . " foi criado.",
                        'order_id' => $order->identify,
                        'order_total' => $order->total,
                        'client_name' => $order->client->name ?? 'Cliente',
                        'notifiable_type' => \App\Models\Order::class,
                        'notifiable_id' => $order->id,
                    ]
                );
            }

            Log::info('SendOrderCreatedNotification: Notifications sent', [
                'order_id' => $order->id,
                'notified_users' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error('SendOrderCreatedNotification: Error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

