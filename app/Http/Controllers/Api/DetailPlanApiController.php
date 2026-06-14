<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreUpdateDetailPlanRequest;
use App\Http\Resources\DetailPlanResource;
use App\Services\DetailPlanService;
use App\Services\PlanService;
use Exception;

class DetailPlanApiController extends Controller
{
    public function __construct(protected DetailPlanService $detailPlanService, protected PlanService $planService)
    {
    }

    public function index(string $planIdentifier)
    {
        try {
            $plan = $this->findPlan($planIdentifier);
            if (!$plan) {
                return ApiResponseClass::sendResponse('', 'Detalhe do plano não encontrado', 404);
            }

            $details = $plan->details()->get();

            return ApiResponseClass::sendResponse(
                DetailPlanResource::collection($details)->resolve(),
                '',
                200
            );
        } catch (Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function store(StoreUpdateDetailPlanRequest $request, string $planIdentifier)
    {
        try {
            $plan = $this->findPlan($planIdentifier);

            if (!$plan) {
                return ApiResponseClass::sendResponse('', 'Detalhe do plano não encontrado', 404);
            }

            $detailPlan = $plan->details()->create($request->all());

            return ApiResponseClass::sendResponse((new DetailPlanResource($detailPlan))->resolve(), 'Detalhe do plano adicionado com sucesso', 201);
        } catch (Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function update(StoreUpdateDetailPlanRequest $request, string $planIdentifier, $idDetailPlan)
    {
        try {
            $plan = $this->findPlan($planIdentifier);

            if (!$plan) {
                return ApiResponseClass::sendResponse('', 'Detalhe do plano não encontrado', 404);
            }

            $detailPlan = $plan->details()->find($idDetailPlan);

            if (!$detailPlan) {
                return ApiResponseClass::sendResponse('', 'Detalhe do plano não encontrado', 404);
            }

            $detailPlan->update($request->all());

            return ApiResponseClass::sendResponse((new DetailPlanResource($detailPlan))->resolve(), 'Detalhe do plano atualizado com sucesso', 200);
        } catch (Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    private function findPlan(string $identifier)
    {
        if (is_numeric($identifier)) {
            return $this->planService->getById((int) $identifier);
        }

        return $this->planService->getByUrl($identifier);
    }
}
