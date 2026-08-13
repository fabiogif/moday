<?php

namespace App\Repositories;

use App\Models\TenantBankAccount;
use App\Repositories\Contracts\BankAccountRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BankAccountRepository extends BaseRepository implements BankAccountRepositoryInterface
{
    public function __construct(protected Model $entity = new TenantBankAccount())
    {
    }

    public function listByTenant(int $tenantId): Collection
    {
        return $this->entity
            ->forTenant($tenantId)
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->get();
    }

    public function createForTenant(array $data): TenantBankAccount
    {
        return $this->entity->create($data);
    }

    public function findByUuidForTenant(string $uuid, int $tenantId): ?TenantBankAccount
    {
        return $this->entity
            ->where('uuid', $uuid)
            ->forTenant($tenantId)
            ->first();
    }

    public function updateByUuidForTenant(string $uuid, int $tenantId, array $payload): bool
    {
        return $this->entity
            ->where('uuid', $uuid)
            ->forTenant($tenantId)
            ->update($payload) > 0;
    }

    public function deleteByUuidForTenant(string $uuid, int $tenantId): bool
    {
        $account = $this->findByUuidForTenant($uuid, $tenantId);

        if (!$account) {
            return false;
        }

        return (bool) $account->delete();
    }
}

