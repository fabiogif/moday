<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\Seasonal\HolidayCalendarService;
use App\Services\Seasonal\ProductAlertService;
use App\Services\Seasonal\SeasonalTrendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeasonalAlertsController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly ProductAlertService $productAlertService,
        private readonly HolidayCalendarService $holidayCalendarService,
        private readonly SeasonalTrendService $trendService,
    ) {}

    /** GET /api/seasonal/alerts — list unacknowledged alerts */
    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = $request->only(['type', 'priority']);
            $perPage = (int) $request->get('per_page', 30);

            $alerts = $this->productAlertService->listForTenant($tenantId, $filters, min($perPage, 100));

            return ApiResponseClass::sendResponse($alerts, 'Alertas recuperados', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar alertas');
        }
    }

    /** GET /api/seasonal/alerts/summary */
    public function summary(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            return ApiResponseClass::sendResponse(
                $this->productAlertService->summary($tenantId),
                'Resumo de alertas',
                200,
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar resumo de alertas');
        }
    }

    /** POST /api/seasonal/alerts/generate — scan batches and generate alerts */
    public function generate(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $batchAlerts   = $this->productAlertService->generateBatchAlerts($tenantId);
            $holidayAlerts = $this->holidayCalendarService->generateAlerts($tenantId);
            $peakAlerts    = $this->trendService->generatePeakAlerts($tenantId);

            return ApiResponseClass::sendResponse([
                'created_batch_alerts'   => $batchAlerts,
                'created_holiday_alerts' => $holidayAlerts,
                'created_peak_alerts'    => $peakAlerts,
                'total_created'          => $batchAlerts + $holidayAlerts + $peakAlerts,
            ], 'Alertas gerados', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar alertas');
        }
    }

    /** PATCH /api/seasonal/alerts/{id}/acknowledge */
    public function acknowledge(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $alert = $this->productAlertService->acknowledge($tenantId, $id, $user->id);

            return ApiResponseClass::sendResponse($alert, 'Alerta reconhecido', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao reconhecer alerta');
        }
    }

    /** POST /api/seasonal/alerts/acknowledge-all?type=expiring_lot */
    public function acknowledgeAll(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $count = $this->productAlertService->acknowledgeAll(
                $tenantId,
                $user->id,
                $request->get('type'),
            );

            return ApiResponseClass::sendResponse(
                ['acknowledged' => $count],
                "{$count} alerta(s) reconhecido(s)",
                200,
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao reconhecer alertas');
        }
    }

    /** GET /api/seasonal/trends?years=2 — monthly revenue trend */
    public function trends(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $years = (int) $request->get('years', 2);

            return ApiResponseClass::sendResponse([
                'monthly_trend'      => $this->trendService->monthlyRevenueTrend($tenantId, min($years, 5)),
                'seasonality_index'  => $this->trendService->seasonalityIndex($tenantId, min($years, 5)),
            ], 'Tendências de consumo', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar tendências');
        }
    }

    /** GET /api/seasonal/trends/month/{month}?years=2 — top products for a given month */
    public function monthDemand(Request $request, int $month): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            if ($month < 1 || $month > 12) {
                return ApiResponseClass::sendResponse(null, 'Mês inválido (1-12)', 422);
            }

            $years = (int) $request->get('years', 2);
            $data  = $this->trendService->productDemandByMonth($tenantId, $month, min($years, 5));

            return ApiResponseClass::sendResponse($data, "Demanda do mês {$month}", 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar demanda do mês');
        }
    }
}
