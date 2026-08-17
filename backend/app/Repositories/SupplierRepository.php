<?php

namespace App\Repositories;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierRepository implements SupplierRepositoryInterface
{
    protected Supplier $entity;

    public function __construct(Supplier $supplier)
    {
        $this->entity = $supplier;
    }

    public function getAllByTenant(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();
    }

    public function paginateForTenant(int $tenantId, int $page, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $query = $this->entity
            ->where('tenant_id', $tenantId)
            ->orderBy('name');

        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('company_name', 'like', $like)
                    ->orWhere('trade_name', 'like', $like)
                    ->orWhere('fantasy_name', 'like', $like)
                    ->orWhere('document', 'like', $like)
                    ->orWhere('cnpj', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getById(int $id)
    {
        return $this->entity->find($id);
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function getByDocument(string $document)
    {
        return $this->entity->where('document', $document)->first();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $supplier = $this->getById($id);
        if ($supplier) {
            $supplier->update($data);
            return $supplier->fresh();
        }
        return null;
    }

    public function delete(int $id)
    {
        $supplier = $this->getById($id);
        if ($supplier) {
            return $supplier->delete();
        }
        return false;
    }

    public function hasExpenses(int $id): bool
    {
        $supplier = $this->getById($id);
        if (!$supplier) {
            return false;
        }
        
        // Verificar expenses
        $hasExpenses = $supplier->expenses()->count() > 0;
        
        // Verificar accounts_payable se a tabela existir
        try {
            $hasAccountsPayable = $supplier->accountsPayable()->count() > 0;
        } catch (\Exception $e) {
            $hasAccountsPayable = false;
        }
        
        return $hasExpenses || $hasAccountsPayable;
    }
}

