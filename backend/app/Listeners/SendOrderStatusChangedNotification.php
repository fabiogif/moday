<?php

namespace App\Listeners;

use App\Events\OrderStatusChangedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderStatusChangedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChangedEvent $event): void
    {
        try {
            $order = $event->order;
            $oldStatus = $event->oldStatus;
            $newStatus = $event->newStatus;

            // Notificar usuários do tenant
            $users = \App\Models\User::where('tenant_id', $order->tenant_id)
                ->where('is_active', true)
                ->get();

            foreach ($users as $user) {
                $this->notificationService->send(
                    $user->id,
                    $order->tenant_id,
                    'order_status_changed',
                    [
                        'title' => 'Status do Pedido Atualizado',
                        'message' => "Pedido #{$order->identify} mudou de '{$oldStatus}' para '{$newStatus}'.",
                        'order_id' => $order->identify,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'notifiable_type' => \App\Models\Order::class,
                        'notifiable_id' => $order->id,
                    ]
                );
            }

            Log::info('SendOrderStatusChangedNotification: Notifications sent', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('SendOrderStatusChangedNotification: Error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

