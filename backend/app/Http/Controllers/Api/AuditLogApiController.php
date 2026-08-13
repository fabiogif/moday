<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = $request->only(['entity_type', 'entity_id', 'action', 'user_id', 'date_from', 'date_to']);
            $perPage = (int) $request->get('per_page', 50);

            $logs = $this->auditService->list($tenantId, $filters, min($perPage, 200));

            return ApiResponseClass::sendResponse($logs, 'Auditoria recuperada', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar auditoria');
        }
    }
}
