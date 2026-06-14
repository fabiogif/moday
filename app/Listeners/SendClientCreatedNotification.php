<?php

namespace App\Listeners;

use App\Events\ClientCreatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendClientCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ClientCreatedEvent $event): void
    {
        try {
            $client = $event->client;

            // Notificar administradores e vendedores
            $users = \App\Models\User::where('tenant_id', $client->tenant_id)
                ->where('is_active', true)
                ->whereHas('profiles', function ($query) {
                    $query->whereIn('name', ['Admin', 'Vendedor']);
                })
                ->get();

            foreach ($users as $user) {
                $this->notificationService->send(
                    $user->id,
                    $client->tenant_id,
                    'client_created',
                    [
                        'title' => 'Novo Cliente Cadastrado',
                        'message' => "O cliente {$client->name} foi cadastrado no sistema.",
                        'client_name' => $client->name,
                        'client_email' => $client->email ?? '',
                        'client_phone' => $client->phone ?? '',
                        'notifiable_type' => \App\Models\Client::class,
                        'notifiable_id' => $client->id,
                    ]
                );
            }

            Log::info('SendClientCreatedNotification: Notifications sent', [
                'client_id' => $client->id,
                'notified_users' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error('SendClientCreatedNotification: Error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

