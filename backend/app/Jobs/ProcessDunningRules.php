<?php

namespace App\Jobs;

use App\Services\DunningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDunningRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(DunningService $dunning): void
    {
        Log::info('ProcessDunningRules: iniciando');

        $count = $dunning->processAll();

        Log::info("ProcessDunningRules: concluído. {$count} tenants processados.");
    }
}
