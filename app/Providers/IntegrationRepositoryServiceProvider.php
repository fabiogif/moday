<?php

namespace App\Providers;

use App\Repositories\Contracts\{
    IfoodTokenRepositoryInterface,
    IfoodOrderRepositoryInterface,
    IfoodApiLogRepositoryInterface,
    IfoodOauthSessionRepositoryInterface,
    IfoodCatalogSnapshotRepositoryInterface,
    IfoodEventRepositoryInterface
};
use App\Repositories\{
    IfoodTokenRepository,
    IfoodOrderRepository,
    IfoodApiLogRepository,
    IfoodOauthSessionRepository,
    IfoodCatalogSnapshotRepository,
    IfoodEventRepository
};
use Illuminate\Support\ServiceProvider;

class IntegrationRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Integrações externas (atualmente iFood):
     * - Tokens / sessões OAuth
     * - Pedidos
     * - Logs de API
     * - Snapshot de catálogo
     * - Eventos
     */
    public function register(): void
    {
        $this->app->bind(IfoodTokenRepositoryInterface::class, IfoodTokenRepository::class);
        $this->app->bind(IfoodOrderRepositoryInterface::class, IfoodOrderRepository::class);
        $this->app->bind(IfoodApiLogRepositoryInterface::class, IfoodApiLogRepository::class);
        $this->app->bind(IfoodOauthSessionRepositoryInterface::class, IfoodOauthSessionRepository::class);
        $this->app->bind(IfoodCatalogSnapshotRepositoryInterface::class, IfoodCatalogSnapshotRepository::class);
        $this->app->bind(IfoodEventRepositoryInterface::class, IfoodEventRepository::class);
    }

    public function boot(): void
    {
        //
    }
}


