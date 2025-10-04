<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Resources\TenantResource;
use App\Services\TenantService;
use Illuminate\Http\{Request, JsonResponse, Response};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


class TenantApiController extends Controller
{
    public function __construct(private readonly TenantService $tenantService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $tenant = $this->tenantService->paginate(
            page: $request->get('page', 1),
            totalPerPage:$request->get('per_page',15),
            filter: $request->filter?? '',
        );
        return ApiResponseClass::sendResponsePaginate(TenantResource::class, $tenant,  200);
    }
    public function store(StoreTenantRequest $request):JsonResponse
    {
        try {
            $tenant = $this->tenantService->store($request->all());
            return ApiResponseClass::sendResponse(new TenantResource($tenant), 'Empresa cadastrada com sucesso', Response::HTTP_CREATED);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }
    public function show(string $uuid):JsonResponse
    {
        $tenant = $this->tenantService->getTenantByUuid($uuid);
        if(!$tenant){
            return ApiResponseClass::sendResponse('', 'Empresa não encontrada', 404);
        }
        return ApiResponseClass::sendResponse(new TenantResource($tenant), '', 200);
    }

}
