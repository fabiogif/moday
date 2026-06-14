<?php

namespace App\Providers;

use App\Repositories\BatchRepository;
use App\Repositories\Contracts\BatchRepositoryInterface;
use App\Repositories\Contracts\FeatureDefinitionRepositoryInterface;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Repositories\Contracts\ReorderSuggestionRepositoryInterface;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Repositories\FeatureDefinitionRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\ReorderSuggestionRepository;
use App\Repositories\SaleOrderRepository;
use App\Repositories\StockMovementRepository;
use App\Repositories\WarehouseRepository;
use Illuminate\Support\ServiceProvider;

class DistribtecRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SaleOrderRepositoryInterface::class, SaleOrderRepository::class);
        $this->app->bind(PurchaseOrderRepositoryInterface::class, PurchaseOrderRepository::class);
        $this->app->bind(BatchRepositoryInterface::class, BatchRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
        $this->app->bind(FeatureDefinitionRepositoryInterface::class, FeatureDefinitionRepository::class);
        $this->app->bind(ReorderSuggestionRepositoryInterface::class, ReorderSuggestionRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
