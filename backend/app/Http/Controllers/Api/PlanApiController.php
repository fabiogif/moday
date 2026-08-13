<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\{StorePlanRequest, UpdatePlanRequest};
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Database\QueryException;
use Illuminate\Http\{Request, Resources\Json\AnonymousResourceCollection, Response, JsonResponse};
use Illuminate\Support\Facades\Log;

class PlanApiController extends Controller
{
    public function __construct(private readonly PlanService $planService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        // For the public landing page, return all active plans with their enabled features
        if ($request->routeIs('*.public.*') || str_contains($request->path(), 'public')) {
            $plans = Plan::where('is_active', true)
                ->with(['details', 'features' => fn($q) => $q->where('is_enabled', true)->with('definition')])
                ->orderBy('price')
                ->get();

            return PlanResource::collection($plans);
        }

        $plans = $this->planService->paginate(
            page: $request->get('page', 1),
            totalPerPage: $request->get('per_page', 15),
            filter: $request->filter ?? '',
        );
        return ApiResponseClass::sendResponsePaginate(PlanResource::class, $plans, 200);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $details = $data['details'] ?? [];

            if ($this->planService->urlExists($data['url'])) {
                return ApiResponseClass::validationError(['url' => ['A url informada já está em uso.']], 'Erro de validação');
            }

            try {
                $plan = $this->planService->storeWithDetails($data, $details);
            } catch (QueryException $exception) {
                if (
                    $exception->getCode() === '23000'
                    || str_contains(strtolower($exception->getMessage()), 'unique')
                ) {
                    return ApiResponseClass::validationError(['url' => ['A url informada já está em uso.']], 'Erro de validação');
                }

                throw $exception;
            }

            return ApiResponseClass::sendResponse(new PlanResource($plan), 'Plano adicionado com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function update(UpdatePlanRequest $request, $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $details = $data['details'] ?? [];

            $this->planService->updateWithDetails($data, $id, $details);

            $plan = $this->planService->getById($id);

            if (!$plan) {
                return ApiResponseClass::sendResponse('', 'Plano não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(new PlanResource($plan), 'Plano atualizado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function show($id): JsonResponse
    {
        $plan = Plan::with([
            'details',
            'features' => fn($q) => $q->where('is_enabled', true)->with('definition'),
        ])->find($id);

        if (!$plan) {
            return ApiResponseClass::sendResponse('', 'Plano não encontrado', Response::HTTP_NOT_FOUND);
        }
        return ApiResponseClass::sendResponse(new PlanResource($plan), '', 200);
    }

    public function delete($id): JsonResponse
    {
        try {
            $deleted = $this->planService->delete($id);

            if (!$deleted) {
                return ApiResponseClass::sendResponse('', 'Plano não encontrado', 404);
            }

            return ApiResponseClass::sendResponse('', 'Plano deletado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }
}
