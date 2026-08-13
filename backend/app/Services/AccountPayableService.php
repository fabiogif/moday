<?php

namespace App\Services;

use App\Repositories\Contracts\AccountPayableRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountPayableService
{
    public function __construct(
        private readonly AccountPayableRepositoryInterface $accountPayableRepository
    ) {}

    public function getAllAccountsPayable(int $tenantId, array $filters = [])
    {
        return $this->accountPayableRepository->getAllByTenant($tenantId, $filters);
    }

    // Alias para compatibilidade
    public function getAllAccounts(int $tenantId, array $filters = [])
    {
        return $this->getAllAccountsPayable($tenantId, $filters);
    }

    public function getAccountPayableByUuid(string $uuid)
    {
        return $this->accountPayableRepository->getByUuid($uuid);
    }

    // Alias para compatibilidade
    public function getAccountByUuid(string $uuid)
    {
        return $this->getAccountPayableByUuid($uuid);
    }

    public function createAccountPayable(array $data)
    {
        DB::beginTransaction();
        try {
            // Se não foi informado valor pago, deixar null
            if (!isset($data['amount_paid'])) {
                $data['amount_paid'] = null;
            }

            $account = $this->accountPayableRepository->create($data);

            DB::commit();
            Log::info('Conta a pagar criada', ['account_id' => $account->id, 'tenant_id' => $data['tenant_id'] ?? null]);
            
            return $account;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar conta a pagar: ' . $e->getMessage());
            throw $e;
        }
    }

    // Alias para compatibilidade
    public function createAccount(array $data, int $tenantId)
    {
        $data['tenant_id'] = $tenantId;
        return $this->createAccountPayable($data);
    }

    public function updateAccountPayable(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $updated = $this->accountPayableRepository->update($id, $data);

            DB::commit();
            Log::info('Conta a pagar atualizada', ['account_id' => $id]);
            
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar conta a pagar: ' . $e->getMessage());
            throw $e;
        }
    }

    // Alias para compatibilidade
    public function updateAccount(int $id, array $data)
    {
        return $this->updateAccountPayable($id, $data);
    }

    public function deleteAccountPayable(int $id)
    {
        DB::beginTransaction();
        try {
            $result = $this->accountPayableRepository->delete($id);

            DB::commit();
            Log::info('Conta a pagar excluída', ['account_id' => $id]);
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir conta a pagar: ' . $e->getMessage());
            throw $e;
        }
    }

    // Alias para compatibilidade
    public function deleteAccount(int $id)
    {
        return $this->deleteAccountPayable($id);
    }

    public function markAsPaid(int $id, array $paymentData)
    {
        DB::beginTransaction();
        try {
            $account = $this->accountPayableRepository->getById($id);
            
            if (!$account) {
                throw new \Exception('Conta a pagar não encontrada.');
            }

            // Determinar o valor pago
            $amountPaid = $paymentData['amount_paid'] ?? $account->amount;
            
            // Determinar o status baseado no valor pago
            if ($amountPaid >= $account->amount) {
                $status = 'pago';
            } elseif ($amountPaid > 0) {
                $status = 'parcial';
            } else {
                $status = $account->status;
            }

            $updateData = [
                'payment_date' => $paymentData['payment_date'] ?? now()->format('Y-m-d'),
                'amount_paid' => $amountPaid,
                'payment_method_id' => $paymentData['payment_method_id'] ?? null,
                'status' => $status,
            ];

            if (isset($paymentData['notes'])) {
                $updateData['notes'] = $paymentData['notes'];
            }

            $updated = $this->accountPayableRepository->update($id, $updateData);

            DB::commit();
            Log::info('Pagamento registrado', ['account_id' => $id, 'amount_paid' => $amountPaid]);
            
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao registrar pagamento: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getDueAlerts(int $tenantId, int $days = 7)
    {
        return $this->accountPayableRepository->getDueAlerts($tenantId, $days);
    }

    public function getStats(int $tenantId, array $params = [])
    {
        $pending = $this->accountPayableRepository->getTotalByStatus($tenantId, 'pendente');
        $paid = $this->accountPayableRepository->getTotalByStatus($tenantId, 'pago');
        $overdue = $this->accountPayableRepository->getTotalByStatus($tenantId, 'vencido');

        return [
            'total_pending' => $pending,
            'total_paid' => $paid,
            'total_overdue' => $overdue,
        ];
    }

    public function payMultipleAccounts(array $accountIds, array $paymentData, int $tenantId)
    {
        DB::beginTransaction();
        try {
            $paid = $this->accountPayableRepository->bulkPay($accountIds, $paymentData);

            DB::commit();
            Log::info('Pagamento em lote realizado', [
                'tenant_id' => $tenantId,
                'count' => count($paid),
                'total' => array_sum(array_column($paid, 'paid_amount'))
            ]);
            
            return $paid;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro no pagamento em lote: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getOverdueAccounts(int $tenantId)
    {
        return $this->accountPayableRepository->getOverdue($tenantId);
    }

    public function getUpcomingAccounts(int $tenantId, int $days = 7)
    {
        return $this->accountPayableRepository->getUpcoming($tenantId, $days);
    }
}



