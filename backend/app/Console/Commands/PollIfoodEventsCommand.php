<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIfoodEventJob;
use App\Ports\Integrations\Ifood\IfoodOrderPort;
use App\Repositories\Contracts\IfoodTokenRepositoryInterface;
use App\Services\Integrations\Ifood\IfoodEventService;
use App\Services\Integrations\Ifood\IfoodTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollIfoodEventsCommand extends Command
{
    protected $signature = 'ifood:poll-events';

    protected $description = 'Consulta e processa eventos pendentes do iFood para tenants ativos';

    public function handle(
        IfoodTokenRepositoryInterface $tokenRepository,
        IfoodTokenService $tokenService,
        IfoodOrderPort $orderPort,
        IfoodEventService $eventService
    ): int {
        $tokens = $tokenRepository->allActive();

        if ($tokens->isEmpty()) {
            $this->info('Nenhum tenant com integração iFood ativa.');
            return self::SUCCESS;
        }

        $tenantIds = $tokens->pluck('tenant_id')->unique();

        foreach ($tenantIds as $tenantId) {
            try {
                $accessToken = $tokenService->getValidAccessToken($tenantId);
                $events = $orderPort->pollEvents($accessToken);

                if (empty($events)) {
                    continue;
                }

                $eventIds = [];

                foreach ($events as $event) {
                    $eventId = $event['id'] ?? null;
                    if ($eventId) {
                        $eventIds[] = $eventId;
                    }

                    $eventModel = $eventService->recordEvent($tenantId, $event);

                    if ($eventModel->status === 'pending') {
                        ProcessIfoodEventJob::dispatch($eventModel->id);
                    }
                }

                if (!empty($eventIds)) {
                    $orderPort->acknowledgeEvents($accessToken, $eventIds);
                    $this->info(sprintf('Tenant %d: %d evento(s) recebidos e confirmados (ACK).', $tenantId, count($eventIds)));
                }
            } catch (\Throwable $exception) {
                Log::channel('ifood')->error('Erro no polling do iFood', [
                    'tenant_id' => $tenantId,
                    'exception' => $exception->getMessage(),
                ]);

                $this->error(sprintf('Tenant %d falhou: %s', $tenantId, $exception->getMessage()));
            }
        }

        return self::SUCCESS;
    }
}
