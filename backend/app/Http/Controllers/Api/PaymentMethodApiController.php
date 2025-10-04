<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Requests\Api\StorePaymentMethodRequest;
use App\Http\Requests\Api\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Services\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentMethodApiController extends Controller
{
    public function __construct(protected PaymentMethodService $paymentMethodService)
    {
    }

    /**
     * Display a listing of payment methods for the authenticated user's tenant.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $paymentMethods = $this->paymentMethodService->getPaymentMethodsByTenant($tenantId);

            return ApiResponseClass::sendResponse(
                PaymentMethodResource::collection($paymentMethods),
                'Formas de pagamento listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar formas de pagamento');
        }
    }

    /**
     * Store a newly created payment method.
     */
    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $paymentMethod = $this->paymentMethodService->createPaymentMethod([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ], $tenantId);

            return ApiResponseClass::sendResponse(
                new PaymentMethodResource($paymentMethod),
                'Forma de pagamento criada com sucesso',
                Response::HTTP_CREATED
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar forma de pagamento');
        }
    }

    /**
     * Display the specified payment method.
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $paymentMethod = $this->paymentMethodService->getPaymentMethodByUuid($uuid, $tenantId);

            if (!$paymentMethod) {
                return ApiResponseClass::sendResponse('', 'Forma de pagamento não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new PaymentMethodResource($paymentMethod),
                'Forma de pagamento encontrada',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar forma de pagamento');
        }
    }

    /**
     * Update the specified payment method.
     */
    public function update(UpdatePaymentMethodRequest $request, string $uuid): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $paymentMethod = $this->paymentMethodService->updatePaymentMethod($uuid, [
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ], $tenantId);

            if (!$paymentMethod) {
                return ApiResponseClass::sendResponse('', 'Forma de pagamento não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new PaymentMethodResource($paymentMethod),
                'Forma de pagamento atualizada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar forma de pagamento');
        }
    }

    /**
     * Remove the specified payment method.
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $deleted = $this->paymentMethodService->deletePaymentMethod($uuid, $tenantId);

            if (!$deleted) {
                return ApiResponseClass::sendResponse('', 'Forma de pagamento não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                '',
                'Forma de pagamento excluída com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir forma de pagamento');
        }
    }

    /**
     * Get active payment methods for the authenticated user's tenant.
     */
    public function active(): JsonResponse
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $paymentMethods = $this->paymentMethodService->getActivePaymentMethodsByTenant($tenantId);

            return ApiResponseClass::sendResponse(
                PaymentMethodResource::collection($paymentMethods),
                'Formas de pagamento ativas listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar formas de pagamento ativas');
        }
    }
}