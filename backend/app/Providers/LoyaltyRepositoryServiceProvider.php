<?php

namespace App\Providers;

use App\Repositories\Contracts\{
    LoyaltyProgramRepositoryInterface,
    LoyaltyRewardRepositoryInterface,
    LoyaltyTransactionRepositoryInterface,
    LoyaltyRedemptionRepositoryInterface
};
use App\Repositories\{
    LoyaltyProgramRepository,
    LoyaltyRewardRepository,
    LoyaltyTransactionRepository,
    LoyaltyRedemptionRepository
};
use Illuminate\Support\ServiceProvider;

class LoyaltyRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Programa de fidelidade:
     * - Programas
     * - Recompensas
     * - Transações de pontos
     * - Resgates
     */
    public function register(): void
    {
        $this->app->bind(LoyaltyProgramRepositoryInterface::class, LoyaltyProgramRepository::class);
        $this->app->bind(LoyaltyRewardRepositoryInterface::class, LoyaltyRewardRepository::class);
        $this->app->bind(LoyaltyTransactionRepositoryInterface::class, LoyaltyTransactionRepository::class);
        $this->app->bind(LoyaltyRedemptionRepositoryInterface::class, LoyaltyRedemptionRepository::class);
    }

    public function boot(): void
    {
        //
    }
}


