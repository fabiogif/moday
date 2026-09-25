<?php

namespace App\Console\Commands;

use App\Jobs\RefreshIfoodTokensJob;
use Illuminate\Console\Command;

class RefreshIfoodTokensCommand extends Command
{
    protected $signature = 'ifood:refresh-tokens {--minutes=15 : Intervalo (em minutos) para considerar tokens próximos da expiração}';

    protected $description = 'Dispara a renovação dos tokens ativos do iFood';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        RefreshIfoodTokensJob::dispatchSync($minutes);

        $this->info(sprintf('Processamento de tokens iFood finalizado (janela: %d minutos).', $minutes));

        return self::SUCCESS;
    }
}


