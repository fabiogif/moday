<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\EvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Services\EvaluationService;
use Illuminate\Http\{ Response, JsonResponse};

class EvaluationApiController extends Controller
{
    public function __construct(protected EvaluationService $evaluationService)
    {
    }

    public function store(EvaluationRequest $request):JsonResponse
    {
        try {
            $data = $request->only('stars', 'comment');
            $evaluation = $this->evaluationService->newCeateEvaluation($request->identify, $data);

            return ApiResponseClass::sendResponse(new EvaluationResource($evaluation), 'Avaliação cadastrada com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }


//    public function getEvaluationsByOrder(int $orderId)
//    {
//        $evaluations = $this->evaluationService->getEvaluationsByOrder($orderId);
//        if(!$evaluations)
//        {
//            return  ApiResponseClass::sendResponse('', 'Avaliação não encontrada' ,Response::HTTP_NOT_FOUND);
//        }
//        return ApiResponseClass::sendResponse(new EvaluationResource($evaluations), '', 200);
//    }



}
