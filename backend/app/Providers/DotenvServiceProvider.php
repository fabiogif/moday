<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DotenvServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Suppress Dotenv deprecation warnings
        if (env('DOTENV_SUPPRESS_DEPRECATION_WARNINGS', true)) {
            $this->suppressDotenvDeprecationWarnings();
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Suppress Dotenv deprecation warnings.
     */
    private function suppressDotenvDeprecationWarnings(): void
    {
        // Set error reporting to exclude deprecation warnings
        $originalErrorReporting = error_reporting();
        error_reporting($originalErrorReporting & ~E_DEPRECATED);

        // Register a shutdown function to restore error reporting
        register_shutdown_function(function () use ($originalErrorReporting) {
            error_reporting($originalErrorReporting);
        });
    }
}
