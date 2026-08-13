<?php

namespace App\Repositories;

use App\Models\FinancialCategory;
use App\Repositories\Contracts\FinancialCategoryRepositoryInterface;

class FinancialCategoryRepository implements FinancialCategoryRepositoryInterface
{
    protected FinancialCategory $entity;

    public function __construct(FinancialCategory $category)
    {
        $this->entity = $category;
    }

    public function getAllByTenant(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function getByType(int $tenantId, string $type)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($type) {
                if ($type === 'receita') {
                    $query->where('applies_to_receivable', true);
                } elseif ($type === 'despesa') {
                    $query->where('applies_to_payable', true);
                }
            })
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function getById(int $id)
    {
        return $this->entity->find($id);
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $category = $this->getById($id);
        if ($category) {
            $category->update($data);
            return $category->fresh();
        }
        return null;
    }

    public function delete(int $id)
    {
        $category = $this->getById($id);
        if ($category) {
            return $category->delete();
        }
        return false;
    }

    public function checkDuplicateName(int $tenantId, string $name, string $type, ?int $excludeId = null)
    {
        $query = $this->entity
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->where(function ($q) use ($type) {
                if ($type !== 'ambos') {
                    $q->where('type', $type)->orWhere('type', 'ambos');
                }
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}

