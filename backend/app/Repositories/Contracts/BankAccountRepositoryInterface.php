<?php

namespace App\Repositories\Contracts;

use App\Models\TenantBankAccount;
use Illuminate\Support\Collection;

interface BankAccountRepositoryInterface
{
    /**
     * Lista contas bancárias do tenant.
     */
    public function listByTenant(int $tenantId): Collection;

    /**
     * Cria uma conta bancária para o tenant informado.
     */
    public function createForTenant(array $data): TenantBankAccount;

    /**
     * Obtém uma conta bancária pelo UUID garantindo o tenant.
     */
    public function findByUuidForTenant(string $uuid, int $tenantId): ?TenantBankAccount;

    /**
     * Atualiza uma conta bancária pelo UUID.
     */
    public function updateByUuidForTenant(string $uuid, int $tenantId, array $payload): bool;

    /**
     * Exclui (soft delete) uma conta bancária pelo UUID.
     */
    public function deleteByUuidForTenant(string $uuid, int $tenantId): bool;
}

