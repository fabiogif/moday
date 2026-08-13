<?php

namespace App\Providers;

use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\CouponRepository;
use Illuminate\Support\ServiceProvider;

class MarketingRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Domínio de marketing / crescimento:
     * - Cupons de desconto e promoções
     * - (Outros repositórios de campanhas podem ser adicionados aqui no futuro)
     */
    public function register(): void
    {
        $this->app->bind(CouponRepositoryInterface::class, CouponRepository::class);
    }

    public function boot(): void
    {
        //
    }
}


