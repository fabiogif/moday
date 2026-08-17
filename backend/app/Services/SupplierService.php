<?php

namespace App\Services;

use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly CacheService $cacheService,
    ) {}

    public function getAllSuppliers(int $tenantId)
    {
        return $this->cacheService->getSupplierList(
            $tenantId,
            fn () => $this->supplierRepository->getAllByTenant($tenantId)
        );
    }

    public function paginateSuppliersByTenant(int $tenantId, int $page, int $perPage, ?string $search = null)
    {
        $params = ['page' => $page, 'per_page' => $perPage, 'search' => $search];

        return $this->cacheService->getSupplierListPaginated(
            $tenantId,
            $params,
            fn () => $this->supplierRepository->paginateForTenant($tenantId, $page, $perPage, $search)
        );
    }

    public function getSupplierByUuid(string $uuid)
    {
        return $this->supplierRepository->getByUuid($uuid);
    }

    public function createSupplier(array $data, int $tenantId)
    {
        DB::beginTransaction();
        try {
            $existing = $this->supplierRepository->getByDocument($data['document']);
            if ($existing) {
                throw new \Exception('Já existe um fornecedor cadastrado com este CNPJ/CPF.');
            }

            $data['tenant_id'] = $tenantId;
            $supplier = $this->supplierRepository->create($data);

            DB::commit();
            $this->cacheService->invalidateSupplierCache($tenantId);
            Log::info('Fornecedor criado', ['supplier_id' => $supplier->id, 'tenant_id' => $tenantId]);

            return $supplier;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar fornecedor: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateSupplier(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $supplier = $this->supplierRepository->getById($id);
            if (!$supplier) {
                throw new \Exception('Fornecedor não encontrado.');
            }

            if (isset($data['document']) && $data['document'] !== $supplier->document) {
                $existing = $this->supplierRepository->getByDocument($data['document']);
                if ($existing && $existing->id !== $id) {
                    throw new \Exception('Já existe um fornecedor com este CNPJ/CPF.');
                }
            }

            $updated = $this->supplierRepository->update($id, $data);

            DB::commit();
            $this->cacheService->invalidateSupplierCache($supplier->tenant_id);
            Log::info('Fornecedor atualizado', ['supplier_id' => $id]);

            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar fornecedor: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteSupplier(int $id)
    {
        DB::beginTransaction();
        try {
            $supplier = $this->supplierRepository->getById($id);
            if (!$supplier) {
                throw new \Exception('Fornecedor não encontrado.');
            }

            if ($this->supplierRepository->hasExpenses($id)) {
                throw new \Exception('Não é possível excluir este fornecedor pois existem despesas ou contas vinculadas.');
            }

            $tenantId = $supplier->tenant_id;
            $result = $this->supplierRepository->delete($id);

            DB::commit();
            $this->cacheService->invalidateSupplierCache($tenantId);
            Log::info('Fornecedor excluído', ['supplier_id' => $id]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir fornecedor: ' . $e->getMessage());
            throw $e;
        }
    }

    public function checkDocument(string $document)
    {
        return $this->supplierRepository->getByDocument($document);
    }
}
