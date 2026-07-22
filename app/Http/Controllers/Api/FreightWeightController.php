<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\AuthTenantService;
use App\Services\Logistics\FreightWeightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreightWeightController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly FreightWeightService $freightWeightService,
    ) {}

    /**
     * GET /api/logistics/freight-weight
     */
    public function showSettings(): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $settings = $this->freightWeightService->getSettings($tenantId);

            return ApiResponseClass::sendResponse($settings, 'Configuração de frete peso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar frete peso');
        }
    }

    /**
     * PUT /api/logistics/freight-weight
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'cf' => 'required|numeric|min:0',
                'cv' => 'required|numeric|min:0',
                'mkp' => 'required|numeric|gt:0',
                'charge_mode' => 'required|in:per_kg,per_cte',
            ]);

            $settings = $this->freightWeightService->saveSettings($tenantId, $validated);

            return ApiResponseClass::sendResponse($settings, 'Frete peso atualizado', 200);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao salvar frete peso');
        }
    }

    /**
     * POST /api/deliveries/{shipmentId}/freight-weight
     */
    public function calculate(Request $request, int $shipmentId): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $shipment = Shipment::forTenant($tenantId)
                ->with('saleOrders')
                ->findOrFail($shipmentId);

            $validated = $request->validate([
                'cf' => 'sometimes|numeric|min:0',
                'cv' => 'sometimes|numeric|min:0',
                'mkp' => 'sometimes|numeric|gt:0',
                'charge_mode' => 'sometimes|in:per_kg,per_cte',
                'cte_count' => 'sometimes|numeric|gt:0',
            ]);

            $result = $this->freightWeightService->calculate($shipment, $validated);

            return ApiResponseClass::sendResponse($result, 'Frete peso calculado', 200);
        } catch (StockException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao calcular frete peso');
        }
    }
}
