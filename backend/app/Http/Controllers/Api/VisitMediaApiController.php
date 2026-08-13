<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Visit\StoreVisitMediaRequest;
use App\Http\Resources\Visit\VisitMediaResource;
use App\Services\AuthTenantService;
use App\Services\Visit\VisitMediaService;
use App\Services\Visit\VisitService;
use Illuminate\Http\JsonResponse;

class VisitMediaApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly VisitService $visitService,
        private readonly VisitMediaService $visitMediaService,
    ) {
    }

    public function index(string $uuid): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $visit = $this->visitService->find($tenantId, $uuid, []);
            if (!$visit) {
                return ApiResponseClass::sendResponse(null, 'Visita não encontrada', 404);
            }

            $media = $this->visitMediaService->list($visit);

            return ApiResponseClass::sendResponse(VisitMediaResource::collection($media), 'Mídias da visita listadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar mídias da visita');
        }
    }

    public function store(StoreVisitMediaRequest $request, string $uuid): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $visit = $this->visitService->find($tenantId, $uuid, []);
            if (!$visit) {
                return ApiResponseClass::sendResponse(null, 'Visita não encontrada', 404);
            }

            $validated = $request->validated();
            $media = $this->visitMediaService->store($visit, $validated['type'], $validated['file'], $user->id);

            return ApiResponseClass::sendResponse(new VisitMediaResource($media), 'Mídia anexada à visita com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao anexar mídia à visita');
        }
    }
}
