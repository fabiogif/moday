<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\{StorePlanRequest, UpdatePlanRequest};
use App\Http\Resources\PlanResource;
use App\Services\PlanService;
use Illuminate\Http\{Request, Resources\Json\AnonymousResourceCollection, Response, JsonResponse};

class PlanApiController extends Controller
{
    public function __construct(private readonly PlanService $planService)
    {
    }


    public function index(Request $request):AnonymousResourceCollection
    {
        $plans = $this->planService->paginate(
            page: $request->get('page', 1),
            totalPerPage:$request->get('per_page',15),
            filter: $request->filter?? '',
        );
        return ApiResponseClass::sendResponsePaginate(PlanResource::class, $plans,  200);
    }

    public function store(StorePlanRequest $request):JsonResponse
    {
        try {
            $plans = $this->planService->store($request->all());
            return ApiResponseClass::sendResponse(new PlanResource($plans), 'Plano adicionado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function update(UpdatePlanRequest $request, $id):JsonResponse
    {
        try {
            $this->planService->update($request->all(), $id);
            return ApiResponseClass::sendResponse('Plano atualizado com sucesso', '', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function show($id):JsonResponse
    {
        $plan = $this->planService->getById($id);
        if(!$plan)
        {
            return  ApiResponseClass::sendResponse('', 'Plano não encontrado' ,Response::HTTP_NOT_FOUND);
        }
        return ApiResponseClass::sendResponse(new PlanResource($plan), '', 200);
    }

    public function delete($id):JsonResponse
    {
        try {
            $this->planService->delete($id);
            return  ApiResponseClass::sendResponse('', 'Plano deletado com sucesso', 204);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }
}
