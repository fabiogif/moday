<?php

namespace App\Http\Controllers\Api\Integrations\Ifood;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Resources\Integrations\Ifood\IfoodOrderResource;
use App\Services\Integrations\Ifood\IfoodOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class IfoodOrderController extends Controller
{
    public function __construct(
        private readonly IfoodOrderService $ifoodOrderService,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $this->resolveTenantId($request);
        $perPage = (int) $request->get('per_page', 20);

        $orders = $this->ifoodOrderService->listByTenant($tenantId, $perPage);

        return ApiResponseClass::sendResponsePaginate(IfoodOrderResource::class, $orders, Response::HTTP_OK);
    }

    public function show(Request $request, string $externalOrderId): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $ifoodOrder = $this->ifoodOrderService->getOrderDetails($externalOrderId, $tenantId);

        if (!$ifoodOrder) {
            return ApiResponseClass::sendResponse('', 'Pedido iFood não encontrado.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponseClass::sendResponse(
            new IfoodOrderResource($ifoodOrder),
            'Pedido iFood recuperado com sucesso',
            Response::HTTP_OK
        );
    }

    public function resendStatus(Request $request, string $externalOrderId): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string',
            'reason' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $tenantId = $this->resolveTenantId($request);

        $ifoodOrder = $this->ifoodOrderService->findForTenant($externalOrderId, $tenantId);

        if (!$ifoodOrder) {
            return ApiResponseClass::sendResponse('', 'Pedido iFood não encontrado.', Response::HTTP_NOT_FOUND);
        }

        $this->ifoodOrderService->sendStatusUpdate(
            $ifoodOrder,
            $data['status'],
            $tenantId,
            $data['reason'] ?? null,
            $data['description'] ?? null
        );

        return ApiResponseClass::sendResponse('', 'Status reenviado com sucesso.', Response::HTTP_OK);
    }

    public function confirm(Request $request, string $externalOrderId): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $ifoodOrder = $this->ifoodOrderService->findForTenant($externalOrderId, $tenantId);

        if (!$ifoodOrder) {
            return ApiResponseClass::sendResponse('', 'Pedido iFood não encontrado.', Response::HTTP_NOT_FOUND);
        }

        $this->ifoodOrderService->confirm($ifoodOrder, $tenantId);

        return ApiResponseClass::sendResponse('', 'Pedido confirmado no iFood.', Response::HTTP_OK);
    }

    private function resolveTenantId(Request $request): int
    {
        $tenantId = $request->integer('tenant_id')
            ?? $request->header('X-Tenant-Id')
            ?? Auth::user()?->tenant_id;

        if (!$tenantId) {
            throw ValidationException::withMessages([
                'tenant_id' => 'tenant_id não informado.',
            ]);
        }

        return (int) $tenantId;
    }
}
