<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

// Suppress Dotenv deprecation warnings
require_once __DIR__.'/../app/Helpers/suppress_deprecation.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Configurar rate limiting
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('login', function (Request $request) {
                return Limit::perMinute(10)->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Muitas tentativas de login. Tente novamente em alguns minutos.'
                        ], 429);
                    });
            });

            RateLimiter::for('register', function (Request $request) {
                return Limit::perHour(100)->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Limite de registros atingido. Tente novamente mais tarde.'
                        ], 429);
                    });
            });

            RateLimiter::for('password-reset', function (Request $request) {
                return Limit::perHour(100)->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Muitas tentativas de redefinição de senha. Tente novamente mais tarde.'
                        ], 429);
                    });
            });

            RateLimiter::for('critical', function (Request $request) {
                return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Muitas requisições. Por favor, aguarde um momento.'
                        ], 429);
                    });
            });

            RateLimiter::for('read', function (Request $request) {
                return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
            });

            // Rate limiter específico para eventos
            RateLimiter::for('events', function (Request $request) {
                $key = $request->user()?->id ?? $request->ip();
                
                // Limites diferentes por operação
                if ($request->isMethod('GET')) {
                    return Limit::perMinute(60)->by($key);
                }
                
                if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
                    return Limit::perMinute(30)->by($key);
                }
                
                if ($request->isMethod('DELETE')) {
                    return Limit::perMinute(10)->by($key);
                }
                
                return Limit::perMinute(60)->by($key);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add CORS middleware FIRST - before everything else
        $middleware->prepend(\App\Http\Middleware\GlobalCorsMiddleware::class);
        
        // Add Request ID middleware para rastreabilidade
        $middleware->prepend(\App\Http\Middleware\RequestIdMiddleware::class);
        
        // Add Security Headers middleware
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'acl.permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'csrf.api' => \App\Http\Middleware\VerifyCsrfTokenApi::class,
            'trial.check' => \App\Http\Middleware\CheckTrialStatus::class,
            'tenant.blocked' => \App\Http\Middleware\CheckTenantBlocked::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.permission' => \App\Http\Middleware\AdminPermission::class,
            'admin.log' => \App\Http\Middleware\LogAdminAction::class,
            'plan.feature' => \App\Http\Middleware\CheckPlanFeatures::class,
            'plan.order_limit' => \App\Http\Middleware\CheckOrderLimit::class,
            'plan.user_limit' => \App\Http\Middleware\CheckUserLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
