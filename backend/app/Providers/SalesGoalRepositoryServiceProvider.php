<?php

namespace App\Providers;

use App\Repositories\Contracts\{
    SalesGoalRepositoryInterface,
    AchievementDefinitionRepositoryInterface,
    GamificationProfileRepositoryInterface
};
use App\Repositories\{
    SalesGoalRepository,
    AchievementDefinitionRepository,
    GamificationProfileRepository
};
use Illuminate\Support\ServiceProvider;

class SalesGoalRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalesGoalRepositoryInterface::class, SalesGoalRepository::class);
        $this->app->bind(AchievementDefinitionRepositoryInterface::class, AchievementDefinitionRepository::class);
        $this->app->bind(GamificationProfileRepositoryInterface::class, GamificationProfileRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
