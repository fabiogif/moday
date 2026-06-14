<?php

namespace App\Repositories;

use App\Models\AccountPayable;
use App\Repositories\Contracts\AccountPayableRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AccountPayableRepository implements AccountPayableRepositoryInterface
{
    protected AccountPayable $entity;

    public function __construct(AccountPayable $accountPayable)
    {
        $this->entity = $accountPayable;
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

        return $query->orderBy('due_date', 'asc')->get();
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
        $account = $this->getById($id);
        if ($account) {
            $account->update($data);
            return $account->fresh(['category', 'supplier', 'paymentMethod']);
        }
        return null;
    }

    public function delete(int $id)
    {
        $account = $this->getById($id);
        if ($account) {
            return $account->delete();
        }
        return false;
    }

    public function getOverdue(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->overdue()
            ->with(['category', 'supplier'])
            ->orderBy('due_date')
            ->get();
    }

    public function getUpcoming(int $tenantId, int $days = 7)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->upcoming($days)
            ->with(['category', 'supplier'])
            ->orderBy('due_date')
            ->get();
    }

    public function bulkPay(array $ids, array $paymentData)
    {
        return DB::transaction(function () use ($ids, $paymentData) {
            $updated = [];
            
            foreach ($ids as $id) {
                $account = $this->getById($id);
                if ($account && $account->status === 'pendente') {
                    $account->update([
                        'status' => 'pago',
                        'payment_date' => $paymentData['payment_date'] ?? now(),
                        'paid_amount' => $paymentData['paid_amount'] ?? $account->total_amount,
                        'payment_method_id' => $paymentData['payment_method_id'] ?? $account->payment_method_id,
                        'notes' => $paymentData['notes'] ?? $account->notes,
                    ]);
                    $updated[] = $account->fresh();
                }
            }
            
            return $updated;
        });
    }

    public function getTotalByPeriod(int $tenantId, string $startDate, string $endDate)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('status', 'pago')
            ->sum(DB::raw('amount_paid'));
    }

    public function getDueAlerts(int $tenantId, int $days = 7)
    {
        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays($days)->format('Y-m-d');

        return $this->entity
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pendente', 'parcial'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->with(['category', 'supplier', 'paymentMethod'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getTotalByStatus(int $tenantId, string $status)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->sum('amount');
    }
}

