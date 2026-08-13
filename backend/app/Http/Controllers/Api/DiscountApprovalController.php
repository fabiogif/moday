<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\SaleOrder;
use App\Services\Audit\DiscountApprovalService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountApprovalController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly DiscountApprovalService $approvalService,
    ) {}

    /** GET /api/discount-approvals?status=pending */
    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = $request->only(['status', 'sale_order_id']);
            $perPage = (int) $request->get('per_page', 30);

            $approvals = $this->approvalService->listForTenant($tenantId, $filters, min($perPage, 100));

            return ApiResponseClass::sendResponse($approvals, 'Aprovações de desconto recuperadas', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar aprovações de desconto');
        }
    }

    /** GET /api/discount-approvals/pending-count */
    public function pendingCount(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            return ApiResponseClass::sendResponse(
                ['count' => $this->approvalService->pendingCount($tenantId)],
                'Total de aprovações pendentes',
                200,
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao contar aprovações pendentes');
        }
    }

    /** POST /api/sale-orders/{saleOrderId}/request-discount-approval */
    public function requestApproval(Request $request, int $saleOrderId): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $order = SaleOrder::where('tenant_id', $tenantId)->findOrFail($saleOrderId);

            $approval = $this->approvalService->evaluateDiscount($order, $user);

            if (!$approval) {
                return ApiResponseClass::sendResponse(
                    null,
                    'Desconto dentro do limite — nenhuma aprovação necessária',
                    200,
                );
            }

            return ApiResponseClass::sendResponse($approval, 'Solicitação de aprovação criada', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao solicitar aprovação de desconto');
        }
    }

    /** PATCH /api/discount-approvals/{id}/approve */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

            $approval = $this->approvalService->approve($tenantId, $id, $user, $validated['notes'] ?? null);

            return ApiResponseClass::sendResponse($approval, 'Desconto aprovado', 200);
        } catch (\DomainException $ex) {
            return ApiResponseClass::sendResponse(null, $ex->getMessage(), 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao aprovar desconto');
        }
    }

    /** PATCH /api/discount-approvals/{id}/reject */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

            $approval = $this->approvalService->reject($tenantId, $id, $user, $validated['notes'] ?? null);

            return ApiResponseClass::sendResponse($approval, 'Desconto rejeitado', 200);
        } catch (\DomainException $ex) {
            return ApiResponseClass::sendResponse(null, $ex->getMessage(), 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao rejeitar desconto');
        }
    }
}
