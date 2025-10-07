<?php

namespace App\Providers;

use App\Repositories\contracts\{CategoryRepositoryInterface,
    ClientRepositoryInterface,
    DashboardRepositoryInterface,
    EvaluationRepositoryInterface,
    OrderRepositoryInterface,
    PaymentMethodRepositoryInterface,
    PermissionRepositoryInterface,
    PlanRepositoryInterface,
    ProductRepositoryInterface,
    TableRepositoryInterface,
    TenantRepositoryInterface};
use App\Repositories\{DashboardRepository,
    EvaluationRepository,
    OrderRepository,
    PaymentMethodRepository,
    PermissionRepository,
    PlanRepository,
    ProductRepository,
    CategoryRepository,
    ClientRepository,
    TableRepository,
    TenantRepository};

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(TableRepositoryInterface::class, TableRepository::class);
        $this->app->bind(EvaluationRepositoryInterface::class, EvaluationRepository::class);
        $this->app->bind(PaymentMethodRepositoryInterface::class, PaymentMethodRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
