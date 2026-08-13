<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepositoryInterface;

class ExpenseRepository implements ExpenseRepositoryInterface
{
    protected Expense $entity;

    public function __construct(Expense $expense)
    {
        $this->entity = $expense;
    }

    public function getAllByTenant(int $tenantId, array $filters = [])
    {
        $query = $this->entity
            ->with(['category', 'supplier', 'paymentMethod'])
            ->where('tenant_id', $tenantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('financial_category_id', $filters['category_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('due_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('due_date', 'desc')->get();
    }

    public function getById(int $id)
    {
        return $this->entity->with(['category', 'supplier', 'paymentMethod'])->find($id);
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity->with(['category', 'supplier', 'paymentMethod'])->where('uuid', $uuid)->first();
    }

    public function create(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(int $id, array $data)
    {
        $expense = $this->getById($id);
        if ($expense) {
            $expense->update($data);
            return $expense->fresh(['category', 'supplier', 'paymentMethod']);
        }
        return null;
    }

    public function delete(int $id)
    {
        $expense = $this->getById($id);
        if ($expense) {
            return $expense->delete();
        }
        return false;
    }

    public function getByPeriod(int $tenantId, string $startDate, string $endDate)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->with(['category', 'supplier'])
            ->orderBy('due_date')
            ->get();
    }

    public function getTotalByPeriod(int $tenantId, string $startDate, string $endDate)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('status', 'pago')
            ->sum('amount');
    }

    public function getByCategory(int $tenantId, int $categoryId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('financial_category_id', $categoryId)
            ->with('supplier')
            ->orderBy('due_date', 'desc')
            ->get();
    }

    public function getBySupplier(int $tenantId, int $supplierId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->with('category')
            ->orderBy('due_date', 'desc')
            ->get();
    }

    public function getPendingExpenses(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('status', 'pendente')
            ->with(['category', 'supplier'])
            ->orderBy('due_date')
            ->get();
    }
}

