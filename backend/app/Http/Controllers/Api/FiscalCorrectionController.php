<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\FiscalWebhookEvent;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FiscalCorrectionController extends Controller
{
    public function __construct(private readonly AuthTenantService $authTenantService) {}

    /** POST /api/sale-orders/{id}/fiscal/correction */
    public function correction(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'correction_text' => 'required|string|min:15|max:1000',
            ]);

            $saleOrder = SaleOrder::where('tenant_id', $tenantId)->findOrFail($id);

            if (empty($saleOrder->nfe_key)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido não possui NF-e emitida.',
                ], 422);
            }

            $tenant  = Tenant::findOrFail($tenantId);
            $result  = $this->callFiscalProvider($tenant, 'correction', [
                'nfe_key'         => $saleOrder->nfe_key,
                'correction_text' => $validated['correction_text'],
            ]);

            FiscalWebhookEvent::create([
                'tenant_id'      => $tenantId,
                'provider'       => $tenant->fiscal_integration_provider ?? 'manual',
                'event_type'     => 'nfe_correction_sent',
                'reference_id'   => (string) $id,
                'reference_type' => 'sale_order',
                'payload'        => $result,
            ]);

            return ApiResponseClass::sendResponse($result, 'Carta de correção enviada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao enviar carta de correção');
        }
    }

    /** POST /api/fiscal/nfe/void-range */
    public function voidRange(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'series'        => 'required|string|max:3',
                'number_start'  => 'required|integer|min:1',
                'number_end'    => 'required|integer|min:1|gte:number_start',
                'justification' => 'required|string|min:15|max:500',
            ]);

            $tenant = Tenant::findOrFail($tenantId);
            $result = $this->callFiscalProvider($tenant, 'void_range', $validated);

            FiscalWebhookEvent::create([
                'tenant_id'      => $tenantId,
                'provider'       => $tenant->fiscal_integration_provider ?? 'manual',
                'event_type'     => 'nfe_void_range_sent',
                'reference_id'   => "{$validated['series']}-{$validated['number_start']}-{$validated['number_end']}",
                'reference_type' => 'nfe_range',
                'payload'        => array_merge($validated, ['result' => $result]),
            ]);

            return ApiResponseClass::sendResponse($result, 'Inutilização enviada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao enviar inutilização');
        }
    }

    private function callFiscalProvider(Tenant $tenant, string $action, array $payload): array
    {
        $provider = $tenant->fiscal_integration_provider;
        $config   = $tenant->fiscal_integration_config ?? [];

        if (!$provider || empty($config['api_url'])) {
            // No provider configured — return the payload for manual handling
            Log::info('FiscalCorrectionController: no provider configured, returning payload for manual action', [
                'tenant_id' => $tenant->id,
                'action'    => $action,
            ]);
            return ['status' => 'pending_manual', 'action' => $action, 'payload' => $payload];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . ($config['api_token'] ?? ''),
            'Content-Type'  => 'application/json',
        ])->post("{$config['api_url']}/{$action}", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Erro na integradora fiscal ({$provider}): " . $response->body()
            );
        }

        return $response->json() ?? [];
    }
}
