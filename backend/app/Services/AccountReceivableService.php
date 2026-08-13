<?php

namespace App\Services;

use App\Repositories\Contracts\AccountReceivableRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountReceivableService
{
    public function __construct(
        private readonly AccountReceivableRepositoryInterface $accountReceivableRepository
    ) {}

    public function getAllAccountsReceivable(int $tenantId, array $filters = [])
    {
        return $this->accountReceivableRepository->getAllByTenant($tenantId, $filters);
    }

    // Alias para compatibilidade
    public function getAllAccounts(int $tenantId, array $filters = [])
    {
        return $this->getAllAccountsReceivable($tenantId, $filters);
    }

    public function getAccountReceivableByUuid(string $uuid)
    {
        return $this->accountReceivableRepository->getByUuid($uuid);
    }

    // Alias para compatibilidade
    public function getAccountByUuid(string $uuid)
    {
        return $this->getAccountReceivableByUuid($uuid);
    }

    public function createAccountReceivable(array $data)
    {
        DB::beginTransaction();
        try {
            // Se não foi informado valor recebido, deixar null
            if (!isset($data['amount_received'])) {
                $data['amount_received'] = null;
            }

            $account = $this->accountReceivableRepository->create($data);

            DB::commit();
            Log::info('Conta a receber criada', ['account_id' => $account->id, 'tenant_id' => $data['tenant_id'] ?? null]);
            
            return $account;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar conta a receber: ' . $e->getMessage());
            throw $e;
        }
    }

    // Alias para compatibilidade
    public function createAccount(array $data, int $tenantId)
    {
        $data['tenant_id'] = $tenantId;
        return $this->createAccountReceivable($data);
    }

    public function updateAccountReceivable(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $updated = $this->accountReceivableRepository->update($id, $data);

            DB::commit();
            Log::info('Conta a receber atualizada', ['account_id' => $id]);
            
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar conta a receber: ' . $e->getMessage());
            throw $e;
        }
    }

    // Alias para compatibilidade
    public function updateAccount(int $id, array $data)
    {
        return $this->updateAccountReceivable($id, $data);
    }

    public function deleteAccountReceivable(int $id)
    {
        DB::beginTransaction();
        try {
            $result = $this->accountReceivableRepository->delete($id);

            DB::commit();
            Log::info('Conta a receber excluída', ['account_id' => $id]);
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir conta a receber: ' . $e->getMessage());
            throw $e;
        }
    }

    // Alias para compatibilidade
    public function deleteAccount(int $id)
    {
        return $this->deleteAccountReceivable($id);
    }

    public function markAsReceived(int $id, array $receiptData)
    {
        DB::beginTransaction();
        try {
            $account = $this->accountReceivableRepository->getById($id);
            
            if (!$account) {
                throw new \Exception('Conta a receber não encontrada.');
            }

            // Determinar o valor recebido
            $amountReceived = $receiptData['amount_received'] ?? $account->amount;
            
            // Determinar o status baseado no valor recebido
            if ($amountReceived >= $account->amount) {
                $status = 'recebido';
            } elseif ($amountReceived > 0) {
                $status = 'parcial';
            } else {
                $status = $account->status;
            }

            $updateData = [
                'receipt_date' => $receiptData['receipt_date'] ?? now()->format('Y-m-d'),
                'amount_received' => $amountReceived,
                'payment_method_id' => $receiptData['payment_method_id'] ?? null,
                'status' => $status,
            ];

            if (isset($receiptData['notes'])) {
                $updateData['notes'] = $receiptData['notes'];
            }

            $updated = $this->accountReceivableRepository->update($id, $updateData);

            DB::commit();
            Log::info('Recebimento registrado', ['account_id' => $id, 'amount_received' => $amountReceived]);
            
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao registrar recebimento: ' . $e->getMessage());
            throw $e;
        }
    }

    // Alias para compatibilidade
    public function receivePayment(int $id, array $paymentData)
    {
        DB::beginTransaction();
        try {
            $account = $this->accountReceivableRepository->markAsReceived($id, $paymentData);
            if (!$account) {
                throw new \Exception('Conta não encontrada ou já foi recebida.');
            }

            DB::commit();
            Log::info('Pagamento recebido', ['account_id' => $id, 'amount' => $paymentData['received_amount'] ?? null]);
            
            return $account;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao receber pagamento: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getOverdueAccounts(int $tenantId)
    {
        return $this->accountReceivableRepository->getOverdue($tenantId);
    }

    public function getByOrderId(int $orderId, int $tenantId)
    {
        return $this->accountReceivableRepository->getByOrderId($orderId, $tenantId);
    }

    public function getStats(int $tenantId, array $params = [])
    {
        $pending = $this->accountReceivableRepository->getTotalByStatus($tenantId, 'pendente');
        $received = $this->accountReceivableRepository->getTotalByStatus($tenantId, 'recebido');
        $overdue = $this->accountReceivableRepository->getTotalByStatus($tenantId, 'vencido');

        return [
            'total_pending' => $pending,
            'total_received' => $received,
            'total_overdue' => $overdue,
        ];
    }
}



