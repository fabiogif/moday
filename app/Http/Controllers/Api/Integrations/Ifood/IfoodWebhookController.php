<?php

namespace App\Http\Controllers\Api\Integrations\Ifood;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIfoodEventJob;
use App\Services\Integrations\Ifood\IfoodEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IfoodWebhookController extends Controller
{
    public function __construct(
        private readonly IfoodEventService $eventService,
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

        if (!$secret) {
            return;
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

    private function resolveTenantId(Request $request): int
    {
        $tenantId = $request->integer('tenant_id')
            ?? $request->header('X-Tenant-Id')
            ?? Auth::user()?->tenant_id;

        if (!$tenantId) {
            throw new \InvalidArgumentException('tenant_id não informado.');
        }

        return (int) $tenantId;
    }
}

