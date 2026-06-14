<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Services\TenantService;
use Illuminate\Http\{Request, JsonResponse, Response};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

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

    public function update(UpdateTenantRequest $request, string $uuid):JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Lidar com upload de logo
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $tenant = $this->tenantService->getTenantByUuid($uuid);
                
                if ($tenant) {
                    // Deletar logo antigo se existir
                    if ($tenant->logo) {
                        // Extrair o path do storage da URL
                        $oldPath = str_replace('/storage/', '', parse_url($tenant->logo, PHP_URL_PATH));
                        if (Storage::disk('public')->exists($oldPath)) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }
                    
                    // Fazer upload do novo logo usando FileUploadService
                    $fileUploadService = app(\App\Services\FileUploadService::class);
                    $uploadResult = $fileUploadService->uploadFile(
                        $request->file('logo'), 
                        'logo', 
                        $uuid
                    );
                    $data['logo'] = $uploadResult['path'];
                }
            }
            
            $tenant = $this->tenantService->update($uuid, $data);
            
            if (!$tenant) {
                return ApiResponseClass::sendResponse('', 'Empresa não encontrada', 404);
            }
            
            return ApiResponseClass::sendResponse(new TenantResource($tenant), 'Empresa atualizada com sucesso', Response::HTTP_OK);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

}
