<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\BankAccountLog;
use App\Models\TenantBankAccount;
use App\Repositories\Contracts\BankAccountRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class BankAccountService
{
    public function __construct(
        private readonly BankAccountRepositoryInterface $bankAccountRepository
    ) {
    }

    public function listAccounts(int $tenantId): Collection
    {
        return $this->bankAccountRepository->listByTenant($tenantId);
    }

    public function createAccount(int $tenantId, array $data): TenantBankAccount
    {
        return DB::transaction(function () use ($tenantId, $data) {
            $data['tenant_id'] = $tenantId;
            $bank = Bank::where('code', $data['bank_code'] ?? null)->first();

            $data['bank_name'] = $bank?->name ?? ('Banco ' . ($data['bank_code'] ?? ''));
            $data['is_primary'] = (bool) ($data['is_primary'] ?? false);

            $account = $this->bankAccountRepository->createForTenant($data);

            if ($account->is_primary) {
                $account->setPrimary();
            }

            BankAccountLog::logAction($account, 'created', null, [
                'account_type' => $account->account_type,
                'bank_name' => $account->bank_name,
                'is_primary' => $account->is_primary,
            ]);

            return $account->fresh();
        });
    }

    public function getAccount(string $uuid, int $tenantId): TenantBankAccount
    {
        $account = $this->bankAccountRepository->findByUuidForTenant($uuid, $tenantId);

        if (!$account) {
            throw new ModelNotFoundException('Conta bancária não encontrada');
        }

        return $account;
    }

    public function updateAccount(string $uuid, int $tenantId, array $payload): TenantBankAccount
    {
        return DB::transaction(function () use ($uuid, $tenantId, $payload) {
            $account = $this->getAccount($uuid, $tenantId);
            $oldValues = $account->toArray();

            $this->bankAccountRepository->updateByUuidForTenant($uuid, $tenantId, $payload);

            $account->refresh();

            BankAccountLog::logAction($account, 'updated', $oldValues, $account->toArray());

            return $account;
        });
    }

    public function deleteAccount(string $uuid, int $tenantId): void
    {
        DB::transaction(function () use ($uuid, $tenantId) {
            $account = $this->getAccount($uuid, $tenantId);

            if ($account->is_primary) {
                throw new \RuntimeException('Não é possível excluir a conta principal. Defina outra conta como principal primeiro.');
            }

            BankAccountLog::logAction($account, 'deleted', $account->toArray(), null);

            $this->bankAccountRepository->deleteByUuidForTenant($uuid, $tenantId);
        });
    }

    public function setPrimary(string $uuid, int $tenantId): TenantBankAccount
    {
        return DB::transaction(function () use ($uuid, $tenantId) {
            $account = $this->getAccount($uuid, $tenantId);

            if (!$account->is_active) {
                throw new \RuntimeException('Não é possível definir uma conta inativa como principal.');
            }

            $account->setPrimary();
            $account->refresh();

            BankAccountLog::logAction($account, 'set_primary');

            return $account;
        });
    }

    public function verify(string $uuid, int $tenantId, string $method): TenantBankAccount
    {
        return DB::transaction(function () use ($uuid, $tenantId, $method) {
            $account = $this->getAccount($uuid, $tenantId);

            $account->verify($method);
            $account->refresh();

            BankAccountLog::logAction($account, 'verified', null, [
                'verification_method' => $method,
            ]);

            return $account;
        });
    }

    public function listBanks(): Collection
    {
        return Bank::active()
            ->orderBy('name')
            ->get(['code', 'name', 'full_name', 'supports_pix']);
    }

    public function listLogs(string $uuid, int $tenantId, int $limit = 50): Collection
    {
        $account = $this->getAccount($uuid, $tenantId);

        return BankAccountLog::forAccount($account->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}

