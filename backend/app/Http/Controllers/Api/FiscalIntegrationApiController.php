<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalIntegrationApiController extends Controller
{
    public function __construct(private readonly AuthTenantService $authTenantService) {}

    public function show(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $tenant = Tenant::findOrFail($tenantId);

            $webhookUrl = url("/api/webhooks/fiscal/{$tenantId}");

            return ApiResponseClass::sendResponse([
                'fiscal_integration_provider' => $tenant->fiscal_integration_provider,
                'fiscal_integration_config' => $tenant->fiscal_integration_config ?? [],
                'nfe_series'                  => $tenant->nfe_series ?? '1',
                'webhook_url'                 => $webhookUrl,
                'note'                        => 'Emissão de NF-e via integração terceira. Configure o provedor e o webhook_secret abaixo.',
            ], 'Configuração fiscal recuperada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar configuração fiscal');
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'fiscal_integration_provider' => 'nullable|string|max:100',
                'fiscal_integration_config' => 'nullable|array',
                'nfe_series'                  => 'nullable|string|max:10',
            ]);

            $tenant = Tenant::findOrFail($tenantId);
            $tenant->update($validated);

            return ApiResponseClass::sendResponse([
                'fiscal_integration_provider' => $tenant->fiscal_integration_provider,
                'fiscal_integration_config'     => $tenant->fiscal_integration_config,
                'nfe_series'                  => $tenant->nfe_series,
            ], 'Configuração fiscal atualizada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar configuração fiscal');
        }
    }
}
