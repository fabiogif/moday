<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\Barcode\BarcodeLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBarcodeLookupController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly BarcodeLookupService $barcodeLookupService,
    ) {}

    /**
     * GET /api/product/barcode-lookup/{code}
     */
    public function __invoke(Request $request, string $code): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $result = $this->barcodeLookupService->lookup($code, $tenantId);

            $httpStatus = match ($result['status']) {
                BarcodeLookupService::STATUS_INVALID => 422,
                BarcodeLookupService::STATUS_EXISTING => 200,
                BarcodeLookupService::STATUS_FOUND => 200,
                default => 200,
            };

            return ApiResponseClass::sendResponse($result, $result['message'], $httpStatus);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao consultar código de barras');
        }
    }
}
