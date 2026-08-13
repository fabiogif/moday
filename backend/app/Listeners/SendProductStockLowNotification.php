<?php

namespace App\Listeners;

use App\Events\ProductStockLowEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendProductStockLowNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ProductStockLowEvent $event): void
    {
        try {
            $product = $event->product;
            $currentStock = $event->currentStock;
            $threshold = $event->threshold;

            // Notificar administradores e gerentes
            $users = \App\Models\User::where('tenant_id', $product->tenant_id)
                ->where('is_active', true)
                ->whereHas('profiles', function ($query) {
                    $query->whereIn('name', ['Admin', 'Gerente']);
                })
                ->get();

            foreach ($users as $user) {
                $this->notificationService->send(
                    $user->id,
                    $product->tenant_id,
                    'product_stock_low',
                    [
                        'title' => 'Estoque Baixo',
                        'message' => "O produto '{$product->name}' está com estoque baixo ({$currentStock} unidades restantes).",
                        'product_name' => $product->name,
                        'current_stock' => $currentStock,
                        'threshold' => $threshold,
                        'notifiable_type' => \App\Models\Product::class,
                        'notifiable_id' => $product->id,
                    ]
                );
            }

            Log::info('SendProductStockLowNotification: Notifications sent', [
                'product_id' => $product->id,
                'current_stock' => $currentStock
            ]);

        } catch (\Exception $e) {
            Log::error('SendProductStockLowNotification: Error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

