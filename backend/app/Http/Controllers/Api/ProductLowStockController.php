<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;

class ProductLowStockController extends Controller
{
    public function __construct(private readonly AuthTenantService $authTenantService) {}

    public function index(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            // Available stock = qtd_stock minus safety_stock reserve
            $products = Product::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('min_stock', '>', 0)
                ->whereRaw('(qtd_stock - COALESCE(safety_stock, 0)) <= min_stock')
                ->select('id', 'name', 'sku', 'qtd_stock', 'min_stock', 'safety_stock', 'unit_of_measure')
                ->orderByRaw('(qtd_stock - COALESCE(safety_stock, 0) - min_stock) ASC')
                ->get()
                ->map(fn ($p) => array_merge($p->toArray(), [
                    'available_stock' => max(0, $p->qtd_stock - ($p->safety_stock ?? 0)),
                    'stock_gap'       => $p->min_stock - max(0, $p->qtd_stock - ($p->safety_stock ?? 0)),
                ]));

            return ApiResponseClass::sendResponse($products, 'Produtos com estoque crítico', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar produtos com estoque crítico');
        }
    }
}
