<?php

namespace App\Providers;

use App\Repositories\AdminActionLogRepository;
use App\Repositories\AdminDashboardRepository;
use App\Repositories\AdminMetricsRepository;
use App\Repositories\AdminTenantRepository;
use App\Repositories\AdminUserRepository;
use App\Repositories\PlanMigrationRepository;
use App\Repositories\Contracts\AdminActionLogRepositoryInterface;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use App\Repositories\Contracts\AdminMetricsRepositoryInterface;
use App\Repositories\Contracts\AdminTenantRepositoryInterface;
use App\Repositories\Contracts\AdminUserRepositoryInterface;
use App\Repositories\Contracts\PlanMigrationRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AdminRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminMetricsRepositoryInterface::class, AdminMetricsRepository::class);
        $this->app->bind(AdminDashboardRepositoryInterface::class, AdminDashboardRepository::class);
        $this->app->bind(AdminTenantRepositoryInterface::class, AdminTenantRepository::class);
        $this->app->bind(AdminUserRepositoryInterface::class, AdminUserRepository::class);
        $this->app->bind(AdminActionLogRepositoryInterface::class, AdminActionLogRepository::class);
        $this->app->bind(PlanMigrationRepositoryInterface::class, PlanMigrationRepository::class);
    }
}
