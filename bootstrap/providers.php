<?php

return [
    App\Providers\EventServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class, // legado / compatibilidade

    // Repositórios organizados por domínio
    App\Providers\CoreRepositoryServiceProvider::class,
    App\Providers\FinancialRepositoryServiceProvider::class,
    App\Providers\LoyaltyRepositoryServiceProvider::class,
    App\Providers\IntegrationRepositoryServiceProvider::class,
    App\Providers\AnalyticsRepositoryServiceProvider::class,
    App\Providers\NotificationRepositoryServiceProvider::class,
    App\Providers\MarketingRepositoryServiceProvider::class,
    App\Providers\PDVRepositoryServiceProvider::class,
    App\Providers\DistribtecRepositoryServiceProvider::class,
    App\Providers\AdminRepositoryServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    App\Providers\DotenvServiceProvider::class,
    Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
];
