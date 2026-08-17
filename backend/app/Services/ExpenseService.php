<?php

namespace App\Services;

use App\Repositories\Contracts\ExpenseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenseRepository
    ) {}

    public function getAllExpenses(int $tenantId, array $filters = [])
    {
        return $this->expenseRepository->getAllByTenant($tenantId, $filters);
    }

    public function getExpenseByUuid(string $uuid)
    {
        return $this->expenseRepository->getByUuid($uuid);
    }

    public function createExpense(array $data, int $tenantId)
    {
        DB::beginTransaction();
        try {
            // Validações de negócio
            if ($data['due_date'] < $data['issue_date']) {
                throw new \Exception('A data de vencimento não pode ser anterior à data de emissão.');
            }

            $data['tenant_id'] = $tenantId;
            $expense = $this->expenseRepository->create($data);

            DB::commit();
            Log::info('Despesa criada', ['expense_id' => $expense->id, 'tenant_id' => $tenantId]);
            
            return $expense;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar despesa: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateExpense(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $expense = $this->expenseRepository->getById($id);
            if (!$expense) {
                throw new \Exception('Despesa não encontrada.');
            }

            // Validação: não permitir alterar status de "pago" para "pendente"
            if ($expense->status === 'pago' && isset($data['status']) && $data['status'] === 'pendente') {
                throw new \Exception('Não é possível alterar o status de uma despesa paga para pendente.');
            }

            $updated = $this->expenseRepository->update($id, $data);

            DB::commit();
            Log::info('Despesa atualizada', ['expense_id' => $id]);
            
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar despesa: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteExpense(int $id)
    {
        DB::beginTransaction();
        try {
            $expense = $this->expenseRepository->getById($id);
            if (!$expense) {
                throw new \Exception('Despesa não encontrada.');
            }

            // Remover anexo se existir
            if ($expense->attachment_path) {
                Storage::disk('public')->delete($expense->attachment_path);
            }

            $result = $this->expenseRepository->delete($id);

            DB::commit();
            Log::info('Despesa excluída', ['expense_id' => $id]);
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir despesa: ' . $e->getMessage());
            throw $e;
        }
    }

    public function uploadAttachment(int $id, $file)
    {
        DB::beginTransaction();
        try {
            $expense = $this->expenseRepository->getById($id);
            if (!$expense) {
                throw new \Exception('Despesa não encontrada.');
            }

            // Remover anexo anterior se existir
            if ($expense->attachment_path) {
                Storage::disk('public')->delete($expense->attachment_path);
            }

            // Salvar novo arquivo
            $path = $file->store('expenses/attachments', 'public');
            
            $this->expenseRepository->update($id, ['attachment_path' => $path]);

            DB::commit();
            Log::info('Anexo de despesa salvo', ['expense_id' => $id, 'path' => $path]);
            
            return $path;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao fazer upload de anexo: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getExpensesByPeriod(int $tenantId, string $startDate, string $endDate)
    {
        return $this->expenseRepository->getByPeriod($tenantId, $startDate, $endDate);
    }

    public function getTotalExpenses(int $tenantId, string $startDate, string $endDate)
    {
        return $this->expenseRepository->getTotalByPeriod($tenantId, $startDate, $endDate);
    }

    public function getStats(int $tenantId)
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return [
            'total_month' => $this->expenseRepository->getTotalByPeriod(
                $tenantId,
                $currentMonth->format('Y-m-d'),
                $endOfMonth->format('Y-m-d')
            ),
            'pending' => $this->expenseRepository->getPendingExpenses($tenantId)->count(),
            'paid_month' => $this->expenseRepository->getAllByTenant($tenantId, [
                'status' => 'pago',
                'start_date' => $currentMonth->format('Y-m-d'),
                'end_date' => $endOfMonth->format('Y-m-d'),
            ])->count(),
        ];
    }
}

