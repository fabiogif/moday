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
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('login', function (Request $request) {
                return Limit::perMinute(5)->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Muitas tentativas de login. Tente novamente em alguns minutos.'
                        ], 429);
                    });
            });

            RateLimiter::for('register', function (Request $request) {
                return Limit::perHour(3)->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Limite de registros atingido. Tente novamente mais tarde.'
                        ], 429);
                    });
            });

            RateLimiter::for('password-reset', function (Request $request) {
                return Limit::perHour(3)->by($request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Muitas tentativas de redefinição de senha. Tente novamente mais tarde.'
                        ], 429);
                    });
            });

            RateLimiter::for('critical', function (Request $request) {
                return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Muitas requisições. Por favor, aguarde um momento.'
                        ], 429);
                    });
            });

            RateLimiter::for('read', function (Request $request) {
                return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        $middleware->alias([
            'acl.permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'csrf.api' => \App\Http\Middleware\VerifyCsrfTokenApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
