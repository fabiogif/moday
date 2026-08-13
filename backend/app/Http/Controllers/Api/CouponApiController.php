<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CouponStoreRequest;
use App\Http\Requests\Api\CouponUpdateRequest;
use App\Http\Resources\CouponResource;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CouponApiController extends Controller
{
    public function __construct(private readonly CouponService $couponService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $tenantContext = $this->resolveTenant();
            if ($tenantContext instanceof JsonResponse) {
                return $tenantContext;
            }

            [$tenantId] = $tenantContext;

            $perPage = max((int) $request->integer('per_page', 15), 0);
            $filters = $request->only(['search', 'code', 'type', 'active', 'featured', 'date_from', 'date_to']);

            $result = $this->couponService->listCoupons($tenantId, $filters, $perPage ?: null);

            if ($result instanceof LengthAwarePaginator) {
                return ApiResponseClass::sendResponse([
                    'data' => CouponResource::collection($result)->items(),
                    'pagination' => [
                        'current_page' => $result->currentPage(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'total' => $result->total(),
                    ],
                ], 'Cupons listados com sucesso', Response::HTTP_OK);
            }

            return ApiResponseClass::sendResponse(
                CouponResource::collection($result),
                'Cupons listados com sucesso',
                Response::HTTP_OK
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar cupons');
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $tenantContext = $this->resolveTenant();
            if ($tenantContext instanceof JsonResponse) {
                return $tenantContext;
            }

            [$tenantId] = $tenantContext;

            $stats = $this->couponService->stats($tenantId);

            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', Response::HTTP_OK);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas de cupons');
        }
    }

    public function store(CouponStoreRequest $request): JsonResponse
    {
        try {
            $tenantContext = $this->resolveTenant();
            if ($tenantContext instanceof JsonResponse) {
                return $tenantContext;
            }

            [$tenantId] = $tenantContext;

            $coupon = $this->couponService->createCoupon(
                $request->validated(),
                $tenantId,
                $request->hasFile('image') ? $request->file('image') : null
            );

            return ApiResponseClass::sendResponse(
                new CouponResource($coupon),
                'Cupom criado com sucesso',
                Response::HTTP_CREATED
            );
        } catch (\DomainException $ex) {
            return ApiResponseClass::sendResponse('', $ex->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar cupom');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $tenantContext = $this->resolveTenant();
            if ($tenantContext instanceof JsonResponse) {
                return $tenantContext;
            }

            [$tenantId] = $tenantContext;

            $coupon = $this->couponService->getCouponByUuid($uuid, $tenantId);

            if (!$coupon) {
                return ApiResponseClass::sendResponse('', 'Cupom não encontrado', Response::HTTP_NOT_FOUND);
            }

            return ApiResponseClass::sendResponse(new CouponResource($coupon), 'Cupom encontrado', Response::HTTP_OK);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar cupom');
        }
    }

    public function update(CouponUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $tenantContext = $this->resolveTenant();
            if ($tenantContext instanceof JsonResponse) {
                return $tenantContext;
            }

            [$tenantId] = $tenantContext;

            $coupon = $this->couponService->updateCoupon(
                $uuid,
                $request->validated(),
                $tenantId,
                $request->hasFile('image') ? $request->file('image') : null,
                $request->boolean('remove_image')
            );

            if (!$coupon) {
                return ApiResponseClass::sendResponse('', 'Cupom não encontrado', Response::HTTP_NOT_FOUND);
            }

            return ApiResponseClass::sendResponse(new CouponResource($coupon), 'Cupom atualizado com sucesso', Response::HTTP_OK);
        } catch (\DomainException $ex) {
            return ApiResponseClass::sendResponse('', $ex->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar cupom');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $tenantContext = $this->resolveTenant();
            if ($tenantContext instanceof JsonResponse) {
                return $tenantContext;
            }

            [$tenantId] = $tenantContext;

            $deleted = $this->couponService->deleteCoupon($uuid, $tenantId);

            if (!$deleted) {
                return ApiResponseClass::sendResponse('', 'Cupom não encontrado', Response::HTTP_NOT_FOUND);
            }

            return ApiResponseClass::sendResponse('', 'Cupom removido com sucesso', Response::HTTP_OK);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover cupom');
        }
    }

    public function toggle(Request $request, string $uuid): JsonResponse
    {
        try {
            $tenantContext = $this->resolveTenant();
            if ($tenantContext instanceof JsonResponse) {
                return $tenantContext;
            }

            [$tenantId] = $tenantContext;

            $isActive = $request->boolean('is_active', true);
            $coupon = $this->couponService->toggleCoupon($uuid, $tenantId, $isActive);

            if (!$coupon) {
                return ApiResponseClass::sendResponse('', 'Cupom não encontrado', Response::HTTP_NOT_FOUND);
            }

            return ApiResponseClass::sendResponse(new CouponResource($coupon), 'Status do cupom atualizado', Response::HTTP_OK);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar status do cupom');
        }
    }

    /**
     * Resolve authenticated tenant context.
     *
     * @return array{int}|\Illuminate\Http\JsonResponse
     */
    private function resolveTenant(): array|JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseClass::unauthorized('Usuário não autenticado');
        }

        if (!$user->tenant_id) {
            return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', Response::HTTP_BAD_REQUEST);
        }

        return [(int) $user->tenant_id];
    }
}
