<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AgingReportService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgingReportController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly AgingReportService $agingReportService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $clientFilter = $request->integer('client_id') ?: null;

            $data = $this->agingReportService->generate($tenantId, $clientFilter);

            return ApiResponseClass::sendResponse($data, 'Relatório de aging gerado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar relatório de aging');
        }
    }
}
