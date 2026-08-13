<?php

namespace App\Listeners;

use App\Events\ProductCreatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendProductCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ProductCreatedEvent $event): void
    {
        try {
            $product = $event->product;

            // Notificar administradores
            $users = \App\Models\User::where('tenant_id', $product->tenant_id)
                ->where('is_active', true)
                ->whereHas('profiles', function ($query) {
                    $query->where('name', 'Admin');
                })
                ->get();

            foreach ($users as $user) {
                $this->notificationService->send(
                    $user->id,
                    $product->tenant_id,
                    'product_created',
                    [
                        'title' => 'Novo Produto Cadastrado',
                        'message' => "O produto '{$product->name}' foi adicionado ao catálogo.",
                        'product_name' => $product->name,
                        'product_price' => $product->price,
                        'product_stock' => $product->qtd_stock,
                        'notifiable_type' => \App\Models\Product::class,
                        'notifiable_id' => $product->id,
                    ]
                );
            }

            Log::info('SendProductCreatedNotification: Notifications sent', [
                'product_id' => $product->id
            ]);

        } catch (\Exception $e) {
            Log::error('SendProductCreatedNotification: Error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

