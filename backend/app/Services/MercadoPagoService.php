<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MercadoPagoService
{
    private string $accessToken;
    private string $baseUrl = 'https://api.mercadopago.com';

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token') ?? '';
    }

    // ── One-shot payment (legacy / hybrid mode) ───────────────────────────────

    public function createCardPayment(
        float  $amount,
        string $token,
        string $paymentMethodId,
        string $paymentTypeId,
        int    $installments,
        string $payerEmail,
        string $externalReference
    ): array {
        $amountStr = number_format($amount, 2, '.', '');

        $payload = [
            'type'               => 'online',
            'processing_mode'    => 'automatic',
            'total_amount'       => $amountStr,
            'external_reference' => $externalReference,
            'payer'              => ['email' => $payerEmail],
            'transactions'       => [
                'payments' => [[
                    'amount'         => $amountStr,
                    'payment_method' => [
                        'id'           => $paymentMethodId,
                        'type'         => $paymentTypeId,
                        'token'        => $token,
                        'installments' => $installments,
                    ],
                ]],
            ],
        ];

        Log::info('MercadoPago: criando pagamento', [
            'external_reference' => $externalReference,
            'amount'             => $amountStr,
            'payment_method'     => $paymentMethodId,
        ]);

        $response = Http::withToken($this->accessToken)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->post("{$this->baseUrl}/v1/orders", $payload);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            Log::error('MercadoPago: falha na criação do pagamento', [
                'status'  => $response->status(),
                'message' => $data['message'] ?? 'Erro desconhecido',
            ]);
            return [
                'success' => false,
                'error'   => $data['message'] ?? 'Pagamento não processado pelo Mercado Pago',
            ];
        }

        $firstPayment  = $data['transactions']['payments'][0] ?? [];
        $paymentStatus = $firstPayment['status'] ?? null;
        $approved      = in_array($paymentStatus, ['processed', 'approved']);

        Log::info('MercadoPago: resposta do pagamento', [
            'order_id'       => $data['id'] ?? null,
            'order_status'   => $data['status'] ?? null,
            'payment_status' => $paymentStatus,
            'status_detail'  => $firstPayment['status_detail'] ?? null,
        ]);

        return [
            'success'        => $approved,
            'order_id'       => $data['id'] ?? null,
            'order_status'   => $data['status'] ?? null,
            'payment_status' => $paymentStatus,
            'status_detail'  => $firstPayment['status_detail'] ?? null,
            'transaction_id' => $firstPayment['id'] ?? null,
            'amount'         => $amount,
            'error'          => $approved ? null : ($firstPayment['status_detail'] ?? 'Pagamento não aprovado'),
        ];
    }

    public function getOrder(string $orderId): array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/v1/orders/{$orderId}");

        if (!$response->successful()) {
            return ['success' => false, 'error' => 'Order não encontrada'];
        }

        return ['success' => true, 'data' => $response->json()];
    }

    // ── B2B Payment Link (Preference API) ────────────────────────────────────

    /**
     * Generate a payment preference (link/QR) for a B2B sale order.
     * Returns init_point (checkout link) and qr_code_base64 for Pix.
     */
    public function createPaymentLink(
        float  $amount,
        string $description,
        string $externalReference,
        string $payerEmail,
        string $backUrl = '',
        array  $paymentMethods = []
    ): array {
        $payload = [
            'items' => [[
                'title'      => $description,
                'quantity'   => 1,
                'unit_price' => round($amount, 2),
                'currency_id' => 'BRL',
            ]],
            'payer'              => ['email' => $payerEmail],
            'external_reference' => $externalReference,
            'back_urls'          => [
                'success' => $backUrl ?: config('app.url'),
                'failure' => $backUrl ?: config('app.url'),
            ],
            'auto_return'         => 'approved',
            'payment_methods'     => $paymentMethods ?: [
                'excluded_payment_types' => [],
                'installments'           => 1,
            ],
            'notification_url' => url('/api/webhooks/mercadopago'),
        ];

        Log::info('MercadoPago: criando preferência B2B', [
            'external_reference' => $externalReference,
            'amount'             => $amount,
        ]);

        $response = Http::withToken($this->accessToken)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->post("{$this->baseUrl}/checkout/preferences", $payload);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            Log::error('MercadoPago: falha ao criar preferência', [
                'status'  => $response->status(),
                'message' => $data['message'] ?? 'Erro desconhecido',
            ]);
            return ['success' => false, 'error' => $data['message'] ?? 'Erro ao criar link de pagamento'];
        }

        return [
            'success'      => true,
            'preference_id' => $data['id'] ?? null,
            'init_point'   => $data['init_point'] ?? null,   // redirect link
            'sandbox_url'  => $data['sandbox_init_point'] ?? null,
        ];
    }

    // ── Preapproval (recurring subscriptions) ────────────────────────────────

    /**
     * Create a recurring subscription (Preapproval).
     *
     * @param string $frequency    'months' | 'days'
     * @param int    $frequencyVal Frequency quantity (1 = monthly)
     */
    public function createSubscription(
        string $reason,
        string $payerEmail,
        string $cardToken,
        float  $amount,
        string $backUrl = '',
        string $frequency = 'months',
        int    $frequencyVal = 1
    ): array {
        $payload = [
            'reason'         => $reason,
            'payer_email'    => $payerEmail,
            'card_token_id'  => $cardToken,
            'back_url'       => $backUrl ?: config('app.url'),
            'status'         => 'authorized',
            'auto_recurring' => [
                'frequency'          => $frequencyVal,
                'frequency_type'     => $frequency,
                'transaction_amount' => $amount,
                'currency_id'        => 'BRL',
            ],
        ];

        Log::info('MercadoPago: criando subscription (preapproval)', [
            'reason' => $reason,
            'email'  => $payerEmail,
            'amount' => $amount,
        ]);

        $response = Http::withToken($this->accessToken)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->post("{$this->baseUrl}/preapproval", $payload);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            Log::error('MercadoPago: falha ao criar preapproval', [
                'status'  => $response->status(),
                'message' => $data['message'] ?? 'Erro desconhecido',
                'cause'   => $data['cause'] ?? null,
            ]);
            return [
                'success' => false,
                'error'   => $data['message'] ?? 'Falha ao criar assinatura recorrente',
                'data'    => $data,
            ];
        }

        Log::info('MercadoPago: preapproval criado', [
            'preapproval_id' => $data['id'] ?? null,
            'status'         => $data['status'] ?? null,
        ]);

        return [
            'success'        => true,
            'preapproval_id' => $data['id'] ?? null,
            'status'         => $data['status'] ?? null,
            'init_point'     => $data['init_point'] ?? null,
            'data'           => $data,
        ];
    }

    /** Update subscription amount (upgrade pro-rata or plan change). */
    public function updateSubscriptionAmount(string $preapprovalId, float $newAmount, ?string $reason = null): array
    {
        $payload = ['auto_recurring' => ['transaction_amount' => $newAmount]];
        if ($reason) {
            $payload['reason'] = $reason;
        }

        $response = Http::withToken($this->accessToken)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->put("{$this->baseUrl}/preapproval/{$preapprovalId}", $payload);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            Log::error('MercadoPago: falha ao atualizar preapproval', [
                'preapproval_id' => $preapprovalId,
                'message'        => $data['message'] ?? 'Erro',
            ]);
            return ['success' => false, 'error' => $data['message'] ?? 'Falha ao atualizar assinatura'];
        }

        return ['success' => true, 'data' => $data];
    }

    /** Cancel a subscription (stops future charges). */
    public function cancelSubscription(string $preapprovalId): array
    {
        $response = Http::withToken($this->accessToken)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->put("{$this->baseUrl}/preapproval/{$preapprovalId}", ['status' => 'cancelled']);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            Log::error('MercadoPago: falha ao cancelar preapproval', [
                'preapproval_id' => $preapprovalId,
                'message'        => $data['message'] ?? 'Erro',
            ]);
            return ['success' => false, 'error' => $data['message'] ?? 'Falha ao cancelar assinatura'];
        }

        return ['success' => true, 'data' => $data];
    }

    /** Pause a subscription. */
    public function pauseSubscription(string $preapprovalId): array
    {
        $response = Http::withToken($this->accessToken)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->put("{$this->baseUrl}/preapproval/{$preapprovalId}", ['status' => 'paused']);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            return ['success' => false, 'error' => $data['message'] ?? 'Falha ao pausar assinatura'];
        }

        return ['success' => true, 'data' => $data];
    }

    /** Reactivate a paused or cancelled subscription. */
    public function reactivateSubscription(string $preapprovalId): array
    {
        $response = Http::withToken($this->accessToken)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->put("{$this->baseUrl}/preapproval/{$preapprovalId}", ['status' => 'authorized']);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            return ['success' => false, 'error' => $data['message'] ?? 'Falha ao reativar assinatura'];
        }

        return ['success' => true, 'data' => $data];
    }

    /** Update the card used for recurring charges. */
    public function updateCard(string $preapprovalId, string $cardTokenId): array
    {
        $response = Http::withToken($this->accessToken)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->put("{$this->baseUrl}/preapproval/{$preapprovalId}", ['card_token_id' => $cardTokenId]);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            return ['success' => false, 'error' => $data['message'] ?? 'Falha ao atualizar cartão'];
        }

        return ['success' => true, 'data' => $data];
    }

    /** Get subscription details. */
    public function getSubscription(string $preapprovalId): array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/preapproval/{$preapprovalId}");

        if (!$response->successful()) {
            return ['success' => false, 'error' => 'Subscription não encontrada'];
        }

        return ['success' => true, 'data' => $response->json()];
    }

    /** Search subscriptions by payer email. */
    public function searchSubscriptions(string $payerEmail): array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/preapproval/search", [
                'payer_email' => $payerEmail,
                'status'      => 'authorized',
            ]);

        if (!$response->successful()) {
            return ['success' => false, 'results' => []];
        }

        $data = $response->json() ?? [];
        return ['success' => true, 'results' => $data['results'] ?? []];
    }

    /**
     * Validate MP webhook HMAC-SHA256 signature.
     * x-signature header format: "ts=<ts>,v1=<hash>"
     */
    public function validateWebhookSignature(
        string $xSignature,
        string $xRequestId,
        string $dataId,
        string $secret
    ): bool {
        if (empty($secret)) {
            Log::error('MercadoPago: webhook secret não configurado — rejeitando assinatura por padrão seguro');
            return false;
        }

        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $val] = explode('=', $part, 2) + [null, null];
            if ($key && $val) {
                $parts[trim($key)] = trim($val);
            }
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (!$ts || !$v1) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $expected  = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }

    /** Map MP preapproval status to internal account_status. */
    public function mapMpStatusToInternal(string $mpStatus): ?string
    {
        return match ($mpStatus) {
            'authorized'                    => 'active',
            'pending', 'in_process'         => 'pending',
            'authorized_pending'            => 'under_review',
            'cancelled'                     => 'cancelled',
            'paused'                        => 'suspended',
            default                         => null,
        };
    }

    /** Extract next billing date from preapproval response data. */
    public function extractNextBillingDate(array $preapprovalData): ?\Carbon\Carbon
    {
        $nextDate = $preapprovalData['next_payment_date']
            ?? $preapprovalData['auto_recurring']['end_date']
            ?? null;

        if (!$nextDate) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($nextDate);
        } catch (\Exception) {
            return null;
        }
    }
}
