<?php

namespace App\Http\Controllers\Api;

use App\Models\MpWebhookEvent;
use App\Models\Tenant;
use App\Services\SubscriptionDomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles Mercado Pago webhooks for Preapproval (recurring) subscriptions.
 *
 * Topics handled:
 *   preapproval          — subscription status change
 *   authorized_payment   — recurring charge succeeded
 *   payment              — charge failed or other payment events
 */
class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private readonly SubscriptionDomainService $domain,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $topic  = $request->query('topic') ?? $request->input('type', '');
        $dataId = $request->query('data.id')
            ?? $request->query('id')
            ?? $request->input('data.id', '');

        Log::info('MP Webhook Preapproval recebido', ['topic' => $topic, 'data_id' => $dataId]);

        // Idempotency check
        $mpEventId = "{$topic}:{$dataId}";

        if (MpWebhookEvent::isDuplicate($mpEventId)) {
            Log::info('MP Webhook: evento duplicado ignorado', ['mp_event_id' => $mpEventId]);
            return response()->json(['ok' => true, 'status' => 'duplicate']);
        }

        // Record webhook (status=processed updated on success)
        $webhookRecord = MpWebhookEvent::create([
            'mp_event_id'  => $mpEventId,
            'topic'        => $topic,
            'payload'      => $request->all(),
            'status'       => 'processed',
            'processed_at' => now(),
        ]);

        try {
            match (true) {
                str_starts_with($topic, 'preapproval')       => $this->handlePreapproval($topic, $dataId, $request),
                $topic === 'authorized_payment'              => $this->handlePayment($dataId, $request, approved: true),
                $topic === 'payment'                         => $this->handlePayment($dataId, $request, approved: false),
                default                                      => Log::info("MP Webhook: topic '{$topic}' ignorado"),
            };
        } catch (\Throwable $e) {
            Log::error('MP Webhook: erro ao processar', ['error' => $e->getMessage(), 'topic' => $topic]);
            $webhookRecord->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    private function handlePreapproval(string $topic, string $preapprovalId, Request $request): void
    {
        if (!$preapprovalId) {
            return;
        }

        $tenant = Tenant::where('mp_subscription_id', $preapprovalId)->first();

        if (!$tenant) {
            Log::warning('MP Webhook Preapproval: tenant não encontrado', ['preapproval_id' => $preapprovalId]);
            return;
        }

        $payload = $request->all();
        // Embed the preapproval_id so the domain service can use it
        $payload['id'] = $preapprovalId;

        $this->domain->handlePreapprovalWebhook($tenant, $payload);
    }

    private function handlePayment(string $paymentId, Request $request, bool $approved): void
    {
        if (!$paymentId) {
            return;
        }

        $payload         = $request->all();
        $preapprovalId   = $payload['preapproval_id']
            ?? $payload['data']['preapproval_id']
            ?? null;

        $tenant = null;

        if ($preapprovalId) {
            $tenant = Tenant::where('mp_subscription_id', $preapprovalId)->first();
        }

        if (!$tenant) {
            Log::warning('MP Webhook Payment: tenant não encontrado', [
                'payment_id'     => $paymentId,
                'preapproval_id' => $preapprovalId,
            ]);
            return;
        }

        $this->domain->handlePaymentWebhook($tenant, array_merge($payload, [
            'id'     => $paymentId,
            'status' => $approved ? 'approved' : ($payload['status'] ?? 'rejected'),
        ]));
    }
}
