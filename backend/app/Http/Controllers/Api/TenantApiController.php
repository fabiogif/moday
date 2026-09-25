<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Services\AuthTenantService;
use App\Services\TenantService;
use Illuminate\Http\{Request, JsonResponse, Response};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TenantApiController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly AuthTenantService $authTenantService,
    ) {
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
            $tenant = $this->tenantService->store($request->validated());
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

    /**
     * Valida os campos de uma etapa do wizard de configurações da empresa
     * sem persistir nada, reutilizando as mesmas regras de UpdateTenantRequest.
     */
    public function validateStep(Request $request): JsonResponse
    {
        [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

        $uuid = (string) $request->input('uuid');
        $ownerCheck = $uuid !== '' ? $this->tenantService->getTenantByUuid($uuid) : null;
        if (!$ownerCheck || $ownerCheck->id !== $tenantId) {
            return ApiResponseClass::sendResponse(null, 'Empresa não encontrada', 404);
        }

        $fields = $request->except(['step', 'uuid']);
        $allRules = UpdateTenantRequest::fieldRules();
        $rules = array_intersect_key($allRules, $fields);

        $validator = Validator::make($fields, $rules);
        if ($validator->fails()) {
            return ApiResponseClass::validationError($validator->errors()->toArray());
        }

        return ApiResponseClass::sendResponse(null, 'Dados válidos', 200);
    }

    public function update(UpdateTenantRequest $request, string $uuid):JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $data = $request->validated();

            $ownerCheck = $this->tenantService->getTenantByUuid($uuid);
            if (!$ownerCheck || $ownerCheck->id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Empresa não encontrada', 404);
            }

            // Upload/remoção de logo e capa (arquivos em $data) são tratados no TenantService
            $tenant = $this->tenantService->update($uuid, $data);
            
            if (!$tenant) {
                return ApiResponseClass::sendResponse('', 'Empresa não encontrada', 404);
            }
            
            return ApiResponseClass::sendResponse(new TenantResource($tenant), 'Empresa atualizada com sucesso', Response::HTTP_OK);
        } catch (ValidationException $ex) {
            return ApiResponseClass::validationError($ex->errors());
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

}
