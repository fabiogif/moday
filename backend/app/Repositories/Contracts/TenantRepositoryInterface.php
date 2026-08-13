<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use Illuminate\Support\Collection;

interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    public function storeTenant(array $data);

    public function getTenantByUuid(string $uuid):Tenant|null;

    /**
     * Retorna tenants com relação de plano carregada, com filtros opcionais.
     *
     * @param array<int>|null $tenantIds
     * @param int|null $planId
     * @param string|null $status 'active' | 'inactive' | null
     * @return Collection<int, Tenant>
     */
    public function getTenantsWithPlan(?array $tenantIds = null, ?int $planId = null, ?string $status = null): Collection;

    public function getTenantBySlug(string $slug): ?Tenant;

    public function findById(int $id): ?Tenant;

    public function findActiveBySlug(string $slug): ?Tenant;

    public function createRegisteredTenant(array $data, \App\Models\Plan $plan): Tenant;
}
