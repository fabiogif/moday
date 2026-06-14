<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\Audit\PriceHistoryService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceHistoryController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly PriceHistoryService $priceHistoryService,
    ) {}

    /** GET /api/price-history — list all price changes with optional filters */
    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = $request->only(['product_id', 'field', 'date_from', 'date_to']);
            $perPage = (int) $request->get('per_page', 50);

            $history = $this->priceHistoryService->listForTenant($tenantId, $filters, min($perPage, 200));

            return ApiResponseClass::sendResponse($history, 'Histórico de preços recuperado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar histórico de preços');
        }
    }

    /** GET /api/price-history/products/{productId} — history for a specific product */
    public function product(Request $request, int $productId): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $perPage = (int) $request->get('per_page', 30);
            $history = $this->priceHistoryService->listForProduct($tenantId, $productId, min($perPage, 100));

            return ApiResponseClass::sendResponse($history, 'Histórico do produto recuperado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar histórico do produto');
        }
    }

    /** GET /api/price-history/volatility — top products with most price changes */
    public function volatility(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $days = (int) $request->get('days', 30);
            $data = $this->priceHistoryService->summarizeVolatility($tenantId, min($days, 365));

            return ApiResponseClass::sendResponse($data, 'Volatilidade de preços calculada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao calcular volatilidade de preços');
        }
    }
}
