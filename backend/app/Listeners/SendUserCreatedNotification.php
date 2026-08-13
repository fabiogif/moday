<?php

namespace App\Listeners;

use App\Events\UserCreatedEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendUserCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserCreatedEvent $event): void
    {
        try {
            $user = $event->user;

            // Notificar administradores do tenant
            $admins = \App\Models\User::where('tenant_id', $user->tenant_id)
                ->whereHas('profiles', function ($query) {
                    $query->where('name', 'Admin');
                })
                ->where('id', '!=', $user->id) // Não notificar o próprio usuário
                ->get();

            foreach ($admins as $admin) {
                $this->notificationService->send(
                    $admin->id,
                    $user->tenant_id,
                    'user_created',
                    [
                        'title' => 'Novo Usuário Cadastrado',
                        'message' => "O usuário {$user->name} foi cadastrado no sistema.",
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'notifiable_type' => User::class,
                        'notifiable_id' => $user->id,
                    ]
                );
            }

            Log::info('SendUserCreatedNotification: Notifications sent', [
                'user_id' => $user->id,
                'notified_admins' => $admins->count()
            ]);

        } catch (\Exception $e) {
            Log::error('SendUserCreatedNotification: Error', [
                'error' => $e->getMessage()
            ]);
        }
    }
}

