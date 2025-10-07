<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\Plan;
use App\Models\Category;
use App\Models\Client;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Observers\ProductObserver;
use App\Observers\PlanObserver;
use App\Observers\CategoryObserver;
use App\Observers\ClientObserver;
use App\Observers\TableObserver;
use App\Observers\TenantObserver;
use App\Observers\PaymentMethodObserver;
use App\Observers\OrderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registra os serviços da aplicação
        $this->app->bind(\App\Services\AuthService::class);
        $this->app->singleton(\App\Services\CacheService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define o comprimento padrão das strings para MySQL
        Schema::defaultStringLength(191);

        // Registra os Observers
        Product::observe(ProductObserver::class);
        Plan::observe(PlanObserver::class);
        Category::observe(CategoryObserver::class);
        Client::observe(ClientObserver::class);
        Table::observe(TableObserver::class);
        Tenant::observe(TenantObserver::class);
        PaymentMethod::observe(PaymentMethodObserver::class);
        Order::observe(OrderObserver::class);

        // Configurações para ambiente de produção
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}