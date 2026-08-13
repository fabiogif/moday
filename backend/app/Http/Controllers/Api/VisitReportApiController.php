<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Visit\VisitReportRequest;
use App\Services\AuthTenantService;
use App\Services\Visit\VisitReportService;
use Illuminate\Http\JsonResponse;

class VisitReportApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly VisitReportService $reportService,
    ) {
    }

    public function index(VisitReportRequest $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $data = $this->reportService->summary(
                $tenantId,
                $user,
                $request->input('date_from'),
                $request->input('date_to'),
                (int) $request->input('days', 30),
                $request->filled('user_id') ? (int) $request->input('user_id') : null
            );

            return ApiResponseClass::sendResponse($data, 'Relatório de visitas gerado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar relatório de visitas');
        }
    }
}
