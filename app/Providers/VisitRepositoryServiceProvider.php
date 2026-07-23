<?php

namespace App\Providers;

use App\Repositories\Contracts\VisitRecurrenceRepositoryInterface;
use App\Repositories\Contracts\VisitReportRepositoryInterface;
use App\Repositories\Contracts\VisitRepositoryInterface;
use App\Repositories\VisitRecurrenceRepository;
use App\Repositories\VisitReportRepository;
use App\Repositories\VisitRepository;
use Illuminate\Support\ServiceProvider;

class VisitRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VisitRepositoryInterface::class, VisitRepository::class);
        $this->app->bind(VisitRecurrenceRepositoryInterface::class, VisitRecurrenceRepository::class);
        $this->app->bind(VisitReportRepositoryInterface::class, VisitReportRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
