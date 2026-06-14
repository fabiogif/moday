<?php

namespace App\Http\Middleware;

use App\Services\MercadoPagoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMercadoPagoSignature
{
    public function __construct(private readonly MercadoPagoService $mp) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret     = config('services.mercadopago.webhook_secret', '');
        $xSignature = $request->header('x-signature', '');
        $xRequestId = $request->header('x-request-id', '');
        $dataId     = $request->query('data.id', $request->input('data.id', ''));

        // In legacy/hybrid mode there is no preapproval webhook secret — skip
        if (empty($secret) || config('services.mercadopago.billing_mode', 'legacy') === 'legacy') {
            return $next($request);
        }

        if (!$xSignature || !$xRequestId) {
            Log::warning('VerifyMPSignature: headers ausentes', [
                'ip'           => $request->ip(),
                'x-signature'  => $xSignature,
                'x-request-id' => $xRequestId,
            ]);
            return response()->json(['error' => 'Signature headers missing'], 401);
        }

        if (!$this->mp->validateWebhookSignature($xSignature, $xRequestId, $dataId, $secret)) {
            Log::warning('VerifyMPSignature: assinatura inválida', [
                'ip'           => $request->ip(),
                'x-request-id' => $xRequestId,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
