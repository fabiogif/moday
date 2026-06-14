<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\AuthTenantService;
use App\Services\Seasonal\HolidayCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayCalendarController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly HolidayCalendarService $holidayService,
    ) {}

    /** GET /api/seasonal/holidays?days=60 — upcoming holidays */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $days = (int) $request->get('days', 60);

            return ApiResponseClass::sendResponse(
                $this->holidayService->upcoming($tenantId, min($days, 365)),
                'Datas comemorativas próximas',
                200,
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar datas comemorativas');
        }
    }

    /** GET /api/seasonal/holidays/all */
    public function index(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            return ApiResponseClass::sendResponse(
                $this->holidayService->list($tenantId),
                'Calendário de datas',
                200,
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar calendário');
        }
    }

    /** POST /api/seasonal/holidays — create custom holiday */
    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'name'               => ['required', 'string', 'max:150'],
                'slug'               => ['required', 'string', 'max:100'],
                'month'              => ['nullable', 'integer', 'between:1,12'],
                'day'                => ['nullable', 'integer', 'between:1,31'],
                'specific_date'      => ['nullable', 'date'],
                'type'               => ['nullable', 'string', 'in:national,state,commemorative,custom'],
                'category'           => ['nullable', 'string', 'max:50'],
                'alert_days_before'  => ['nullable', 'integer', 'between:1,120'],
                'notes'              => ['nullable', 'string', 'max:500'],
            ]);

            $holiday = $this->holidayService->create($tenantId, $validated);

            return ApiResponseClass::sendResponse($holiday, 'Data comemorativa criada', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar data comemorativa');
        }
    }

    /** PUT /api/seasonal/holidays/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'name'               => ['sometimes', 'string', 'max:150'],
                'alert_days_before'  => ['nullable', 'integer', 'between:1,120'],
                'notes'              => ['nullable', 'string', 'max:500'],
                'is_active'          => ['sometimes', 'boolean'],
            ]);

            $holiday = $this->holidayService->update($tenantId, $id, $validated);

            return ApiResponseClass::sendResponse($holiday, 'Data atualizada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar data comemorativa');
        }
    }

    /** DELETE /api/seasonal/holidays/{id} */
    public function destroy(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $this->holidayService->delete($tenantId, $id);

            return ApiResponseClass::sendResponse(null, 'Data removida', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover data comemorativa');
        }
    }
}
