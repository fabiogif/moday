<?php

namespace App\Providers;

use App\Adapters\Integrations\Ifood\Http\IfoodAuthHttpAdapter;
use App\Adapters\Integrations\Ifood\Http\IfoodOrderHttpAdapter;
use App\Models\Category;
use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Table;
use App\Models\Tenant;
use App\Observers\CategoryObserver;
use App\Observers\ClientObserver;
use App\Observers\OrderObserver;
use App\Observers\PaymentMethodObserver;
use App\Observers\PlanObserver;
use App\Observers\ProductObserver;
use App\Observers\TableObserver;
use App\Observers\TenantObserver;
use App\Ports\Integrations\Ifood\IfoodAuthPort;
use App\Ports\Integrations\Ifood\IfoodOrderPort;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\AuthService::class);
        $this->app->singleton(\App\Services\CacheService::class);

        $this->app->singleton(IfoodAuthPort::class, function () {
            try {
                return IfoodAuthHttpAdapter::makeFromConfig();
            } catch (\RuntimeException $exception) {
                $config = config('services.ifood', []);

                return new IfoodAuthHttpAdapter(
                    $config['client_id'] ?? '',
                    $config['client_secret'] ?? '',
                    $config['oauth_url'] ?? '',
                    $config['scope'] ?? null,
                );
            }
        });

        $this->app->singleton(IfoodOrderPort::class, function () {
            return IfoodOrderHttpAdapter::makeFromConfig();
        });

        $this->app->singleton(\App\Services\EmailService::class, function ($app) {
            return new \App\Services\EmailService();
        });

        $this->app->singleton('session.handler', function ($app) {
            return new \App\Session\HybridSessionHandler(
                config('session.lifetime') * 60
            );
        });

        $this->app->singleton(\App\RateLimiting\HybridRateLimiter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Product::observe(ProductObserver::class);
        Plan::observe(PlanObserver::class);
        Category::observe(CategoryObserver::class);
        Client::observe(ClientObserver::class);
        Table::observe(TableObserver::class);
        Tenant::observe(TenantObserver::class);
        PaymentMethod::observe(PaymentMethodObserver::class);
        Order::observe(OrderObserver::class);

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
                $formatMap = [
                    '%Y' => 'Y',
                    '%y' => 'y',
                    '%m' => 'm',
                    '%d' => 'd',
                    '%H' => 'H',
                    '%i' => 'i',
                    '%s' => 's',
                    '%b' => 'M',
                    '%M' => 'F',
                ];
                $phpFormat = str_replace(array_keys($formatMap), array_values($formatMap), $format);
                $dt = new \DateTimeImmutable($date);
                return $dt->format($phpFormat);
            });
        }

        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return "{$frontend}/auth/reset-password?token={$token}&email={$email}";
        });
    }
}

