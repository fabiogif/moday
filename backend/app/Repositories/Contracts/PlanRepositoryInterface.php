<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface PlanRepositoryInterface extends BaseRepositoryInterface
{
    public function tenants();
    public function getByUrl(string $urlPlan);

    /**
     * Retorna todos os planos ativos.
     *
     * @return Collection<int, \App\Models\Plan>
     */
    public function getActivePlans(): Collection;

    public function getActivePlansWithDetails(): Collection;

    public function urlExists(string $url, ?int $excludeId = null): bool;

    public function syncDetails(int $planId, array $details): mixed;
}
