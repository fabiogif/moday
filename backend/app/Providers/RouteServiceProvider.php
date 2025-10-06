<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Custom route model binding for Profile with tenant scope
        Route::bind('profile', function ($value) {
            $user = auth('api')->user();
            
            if (!$user) {
                abort(401, 'Não autenticado');
            }
            
            $profile = \App\Models\Profile::where('id', $value)
                ->where('tenant_id', $user->tenant_id)
                ->first();
            
            if (!$profile) {
                abort(404, 'Perfil não encontrado');
            }
            
            return $profile;
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
