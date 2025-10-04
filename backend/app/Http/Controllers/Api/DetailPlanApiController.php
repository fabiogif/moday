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


    public function index(string $urlPlan)
    {
        try {
            $plan = $this->planService->getByUrl($urlPlan);
            if (!$plan) {
                return ApiResponseClass::sendResponse('', 'Detalhe do plano não encontrado', 404);
            }
            $details = $plan->details();

            return ApiResponseClass::sendResponse(new DetailPlanResource($details), '', 200);

        } catch (Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function store(StoreUpdateDetailPlanRequest $request, $urlPlan)
    {
        try {
            $plan = $this->planService->getByUrl($urlPlan);

            if (!$plan) {
                return ApiResponseClass::sendResponse('', 'Detalhe do plano não encontrado', 404);
            }
            $detailPlan = $plan->details()->create($request->all());

            return ApiResponseClass::sendResponse(new DetailPlanResource($detailPlan), 'Detalhe do plano adicionado com sucesso', 200);

        } catch (Exception $ex) {

            return ApiResponseClass::rollback($ex);
        }
    }

    public function update(StoreUpdateDetailPlanRequest $request, $urlPlan, $idDetailPlan)
    {
        try {

            dd($request->all());
            $plan = $this->planService->getByUrl($urlPlan);

            if (!$plan) {
                return ApiResponseClass::sendResponse('', 'Detalhe do plano não encontrado', 404);
            }

            $detailPlan = $plan->details()->find($idDetailPlan);

            if(!$detailPlan){
                return ApiResponseClass::sendResponse('', 'Detalhe do plano não encontrado', 404);
            }

            $detailPlan->update($request->all());

            return ApiResponseClass::sendResponse(new DetailPlanResource($detailPlan), 'Detalhe do plano atualizado com sucesso', 200);

        }
        catch (Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

}
