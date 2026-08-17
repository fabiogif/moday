<?php

namespace App\Providers;

use App\Repositories\Contracts\PDVFeedbackRepositoryInterface;
use App\Repositories\PDVFeedbackRepository;
use Illuminate\Support\ServiceProvider;

class PDVRepositoryServiceProvider extends ServiceProvider
{
    /**
     * PDV / Frente de Caixa:
     * - Feedbacks e avaliações específicos do fluxo de PDV
     */
    public function register(): void
    {
        $this->app->bind(PDVFeedbackRepositoryInterface::class, PDVFeedbackRepository::class);
    }

    public function boot(): void
    {
        //
    }
}


