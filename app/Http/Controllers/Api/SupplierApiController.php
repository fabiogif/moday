<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSupplierRequest;
use App\Http\Requests\Api\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Services\AuthTenantService;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierApiController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $suppliers = $this->supplierService->getAllSuppliers($tenantId);

            return ApiResponseClass::sendResponse(
                SupplierResource::collection($suppliers),
                'Fornecedores recuperados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar fornecedores');
        }
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $data = $this->normalizeSupplierData($request->validated());
            $supplier = $this->supplierService->createSupplier($data, $tenantId);

            return ApiResponseClass::sendResponse(
                new SupplierResource($supplier),
                'Fornecedor criado com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar fornecedor');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $supplier = $this->supplierService->getSupplierByUuid($uuid);

            if (!$supplier || $supplier->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Fornecedor não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new SupplierResource($supplier),
                'Fornecedor recuperado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar fornecedor');
        }
    }

    public function update(UpdateSupplierRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $supplier = $this->supplierService->getSupplierByUuid($uuid);

            if (!$supplier || $supplier->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Fornecedor não encontrado', 404);
            }

            $updated = $this->supplierService->updateSupplier($supplier->id, $this->normalizeSupplierData($request->validated()));

            return ApiResponseClass::sendResponse(
                new SupplierResource($updated),
                'Fornecedor atualizado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar fornecedor');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $supplier = $this->supplierService->getSupplierByUuid($uuid);

            if (!$supplier || $supplier->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Fornecedor não encontrado', 404);
            }

            $this->supplierService->deleteSupplier($supplier->id);

            return ApiResponseClass::sendResponse(null, 'Fornecedor excluído com sucesso', 200);
        } catch (\Exception $ex) {
            // Se o erro for de vínculo, retornar 400
            if (str_contains($ex->getMessage(), 'despesas') || str_contains($ex->getMessage(), 'contas vinculadas')) {
                return ApiResponseClass::sendResponse(null, $ex->getMessage(), 400);
            }
            return ApiResponseClass::rollback($ex, 'Erro ao excluir fornecedor');
        }
    }

    private function normalizeSupplierData(array $data): array
    {
        // Map B2B field names to legacy internal schema (both sets stored)
        if (empty($data['name']) && !empty($data['company_name'])) {
            $data['name'] = $data['company_name'];
        }
        if (empty($data['company_name']) && !empty($data['name'])) {
            $data['company_name'] = $data['name'];
        }
        if (empty($data['fantasy_name']) && !empty($data['trade_name'])) {
            $data['fantasy_name'] = $data['trade_name'];
        }
        if (empty($data['trade_name']) && !empty($data['fantasy_name'])) {
            $data['trade_name'] = $data['fantasy_name'];
        }
        if (empty($data['document']) && !empty($data['cnpj'])) {
            $data['document'] = preg_replace('/\D/', '', $data['cnpj']);
            $data['document_type'] = 'cnpj';
        }
        if (empty($data['cnpj']) && !empty($data['document']) && ($data['document_type'] ?? '') === 'cnpj') {
            $data['cnpj'] = $data['document'];
        }
        if (empty($data['document_type'])) {
            $data['document_type'] = 'cnpj';
        }

        return $data;
    }

    public function checkDocument(Request $request): JsonResponse
    {
        try {
            $document = $request->input('document');
            $supplier = $this->supplierService->checkDocument($document);

            return ApiResponseClass::sendResponse([
                'exists' => $supplier !== null,
                'supplier' => $supplier ? new SupplierResource($supplier) : null,
            ], $supplier ? 'Fornecedor encontrado' : 'Documento disponível', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao verificar documento');
        }
    }
}

