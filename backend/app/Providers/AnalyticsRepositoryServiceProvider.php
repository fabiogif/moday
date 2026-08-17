<?php

namespace App\Providers;

use App\Repositories\Contracts\{
    DashboardRepositoryInterface,
    SalesPerformanceRepositoryInterface,
    ProductUsageRepositoryInterface,
    OrderUsageRepositoryInterface
};
use App\Repositories\{
    DashboardRepository,
    SalesPerformanceRepository,
    ProductUsageRepository,
    OrderUsageRepository
};
use Illuminate\Support\ServiceProvider;

class AnalyticsRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Analytics e métricas:
     * - Dashboards
     * - Performance de vendas
     * - Contadores de uso (produtos / pedidos) para limites de plano
     */
    public function register(): void
    {
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(SalesPerformanceRepositoryInterface::class, SalesPerformanceRepository::class);

        // Helpers de contagem/uso (limites de plano, etc.)
        $this->app->bind(ProductUsageRepositoryInterface::class, ProductUsageRepository::class);
        $this->app->bind(OrderUsageRepositoryInterface::class, OrderUsageRepository::class);
    }

    public function boot(): void
    {
        //
    }
}


