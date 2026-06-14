<?php

namespace App\Providers;

use App\Repositories\Contracts\{
    NotificationRepositoryInterface,
    NotificationPreferenceRepositoryInterface
};
use App\Repositories\{
    NotificationRepository,
    NotificationPreferenceRepository
};
use Illuminate\Support\ServiceProvider;

class NotificationRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Notificações:
     * - Notificações enviadas
     * - Preferências de notificação do usuário
     */
    public function register(): void
    {
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(NotificationPreferenceRepositoryInterface::class, NotificationPreferenceRepository::class);
    }

    public function boot(): void
    {
        //
    }
}


