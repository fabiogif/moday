<?php

namespace App\Http\Controllers\Api\Integrations\Ifood;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIfoodEventJob;
use App\Repositories\Contracts\IfoodTokenRepositoryInterface;
use App\Services\Integrations\Ifood\IfoodEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IfoodWebhookController extends Controller
{
    public function __construct(
        private readonly IfoodEventService $eventService,
        private readonly IfoodTokenRepositoryInterface $tokenRepository,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->assertSignature($request);

        $tenantId = $this->resolveTenantId($request);

        $payload = $request->all();

        if (isset($payload['events']) && is_array($payload['events'])) {
            foreach ($payload['events'] as $event) {
                $this->processEvent($event, $tenantId);
            }
        } elseif (isset($payload['order'])) {
            $this->processEvent($payload, $tenantId);
        } else {
            Log::channel('ifood')->warning('Webhook iFood sem ordem identificada.', [
                'payload' => $payload,
            ]);
        }

        return response()->json([
            'message' => 'Webhook recebido',
        ], Response::HTTP_ACCEPTED);
    }

    private function processEvent(array $event, int $tenantId): void
    {
        try {
            $record = $this->eventService->recordEvent($tenantId, $event);

            ProcessIfoodEventJob::dispatch($record->id);
        } catch (\Throwable $exception) {
            Log::channel('ifood')->error('Erro ao armazenar evento iFood', [
                'event' => $event,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function assertSignature(Request $request): void
    {
        $secret = config('services.ifood.webhook_secret');

        // Fail-closed: sem secret configurado, o webhook não pode ser validado
        // e deve ser rejeitado, nunca aceito sem verificação.
        if (!$secret) {
            Log::channel('ifood')->error('Webhook iFood recebido sem IFOOD_WEBHOOK_SECRET configurado — rejeitado.');
            abort(Response::HTTP_UNAUTHORIZED, 'Webhook não configurado corretamente.');
        }

        $signature = $request->header('X-Signature');

        if (!$signature) {
            abort(Response::HTTP_UNAUTHORIZED, 'Assinatura ausente.');
        }

        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (!hash_equals($signature, $computed)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Assinatura inválida.');
        }
    }

    /**
     * Resolve o tenant dono deste webhook a partir do estado do servidor
     * (quais tenants têm integração iFood ativa), nunca a partir de um
     * tenant_id/X-Tenant-Id informado pelo próprio request não autenticado.
     *
     * A integração iFood hoje é configurada de forma global por deploy
     * (um único IFOOD_CLIENT_ID/IFOOD_MERCHANT_ID em config/services.php),
     * então normalmente existe no máximo um tenant com token ativo. Se
     * houver mais de um (múltiplos merchants no mesmo deploy), como ainda
     * não existe uma tabela de mapeamento merchant_id -> tenant_id, caímos
     * de volta ao tenant_id do payload apenas para desambiguar entre os
     * tenants que já têm integração ativa — nunca aceitamos um tenant sem
     * integração ativa.
     */
    private function resolveTenantId(Request $request): int
    {
        $activeTenantIds = $this->tokenRepository->allActive()
            ->pluck('tenant_id')
            ->unique()
            ->values();
        if ($activeTenantIds->isEmpty()) {
            throw new \InvalidArgumentException('Nenhum tenant com integração iFood ativa.');
        }

        if ($activeTenantIds->count() === 1) {
            return (int) $activeTenantIds->first();
        }

        Log::channel('ifood')->warning('Webhook iFood: múltiplos tenants com integração ativa, desambiguando pelo payload.', [
            'active_tenant_ids' => $activeTenantIds->all(),
        ]);

        $requestedTenantId = $request->integer('tenant_id') ?: $request->header('X-Tenant-Id');

        if (!$requestedTenantId || !$activeTenantIds->contains((int) $requestedTenantId)) {
            throw new \InvalidArgumentException('tenant_id não corresponde a uma integração iFood ativa.');
        }

        return (int) $requestedTenantId;
    }
}

