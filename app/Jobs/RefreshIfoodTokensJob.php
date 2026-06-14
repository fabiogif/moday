<?php

namespace App\Jobs;

use App\Repositories\Contracts\IfoodTokenRepositoryInterface;
use App\Services\Integrations\Ifood\IfoodTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshIfoodTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $minutesAhead = 15
    ) {
    }

    public function handle(
        IfoodTokenRepositoryInterface $tokenRepository,
        IfoodTokenService $tokenService
    ): void {
        $tokens = $tokenRepository->getTokensExpiringWithin($this->minutesAhead);

        $tenantIds = $tokens->pluck('tenant_id')->unique();

        foreach ($tenantIds as $tenantId) {
            try {
                $currentToken = $tokenRepository->getActiveToken($tenantId);

                if (!$currentToken) {
                    $tokenService->generateAndStoreToken($tenantId);
                    continue;
                }

                $tokenService->refreshAndStoreToken($tenantId, $currentToken)
                    ?? $tokenService->generateAndStoreToken($tenantId);
            } catch (\Throwable $exception) {
                Log::channel('ifood')->error('Falha ao renovar token iFood via job', [
                    'tenant_id' => $tenantId,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }
}


