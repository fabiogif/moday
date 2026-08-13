<?php

namespace App\Repositories;

use App\Models\AccountReceivable;
use App\Repositories\Contracts\AccountReceivableRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AccountReceivableRepository implements AccountReceivableRepositoryInterface
{
    protected AccountReceivable $entity;

    public function __construct(AccountReceivable $accountReceivable)
    {
        $this->entity = $accountReceivable;
    }

    public function getAllByTenant(int $tenantId, array $filters = [])
    {
        $query = $this->entity
            ->with(['category', 'client', 'order', 'paymentMethod'])
            ->where('tenant_id', $tenantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('financial_category_id', $filters['category_id']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('due_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('due_date', 'asc')->get();
    }

    public function getById(int $id)
    {
        return $this->entity->with(['category', 'client', 'order', 'paymentMethod'])->find($id);
    }

    public function getByUuid(string $uuid)
    {
        return $this->entity->with(['category', 'client', 'order', 'paymentMethod'])->where('uuid', $uuid)->first();
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
            return $account->fresh(['category', 'client', 'order', 'paymentMethod']);
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

    public function markAsReceived(int $id, array $data)
    {
        $account = $this->getById($id);
        if ($account && $account->status === 'pendente') {
            $account->update([
                'status' => 'recebido',
                'receipt_date' => $data['receipt_date'] ?? now(),
                'amount_received' => $data['amount_received'] ?? $account->amount,
                'payment_method_id' => $data['payment_method_id'] ?? $account->payment_method_id,
                'notes' => $data['notes'] ?? $account->notes,
            ]);
            return $account->fresh();
        }
        return null;
    }

    public function getOverdue(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->overdue()
            ->with(['category', 'client'])
            ->orderBy('due_date')
            ->get();
    }

    public function getTotalExpected(int $tenantId, string $startDate, string $endDate)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->sum(DB::raw('amount + COALESCE(interest, 0) + COALESCE(fine, 0) - COALESCE(discount, 0)'));
    }

    public function getTotalReceived(int $tenantId, string $startDate, string $endDate)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->where('status', 'recebido')
            ->sum('amount_received');
    }

    public function getByOrderId(int $orderId, int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->with(['category', 'client', 'order', 'paymentMethod'])
            ->orderBy('installment_number', 'asc')
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

