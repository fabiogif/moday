<?php

namespace App\Services;

use App\Models\Plan;
use App\Repositories\contracts\PaginateRepositoryInterface;
use App\Repositories\contracts\PlanRepositoryInterface;
use App\Repositories\contracts\TenantRepositoryInterface;

class TenantService
{
    public function __construct(private readonly TenantRepositoryInterface $tenantRepositoryInterface,
                                private readonly PlanRepositoryInterface   $planRepositoryInterface,
                                private Plan                               $plan,
                                private array                              $data = []){}

    public function index(string $filter):PaginateRepositoryInterface
    {
        return $this->tenantRepositoryInterface->index(filter: $filter);
    }

    public function store(array $data)
    {
        $this->plan = $this->planRepositoryInterface->getById($data['plan_id']);
        $this->data = $data;
        $tenant = $this->storeTenant();
        $this->storeUser($tenant);
        return  $tenant;
    }

    public function storeTenant()
    {
        $data = [
            'cnpj' => $this->data['cnpj'],
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'subscription' => now(),
            'expires_at' => now()->addDays(7)];

        return $this->plan->tenants()->create($data);
    }

    public function storeUser($tenant)
    {
        return  $tenant->users()->create([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'password' => bcrypt($this->data['password']),
        ]);
    }

    public function getTenantByUuid(string $uuid)
    {
        return $this->tenantRepositoryInterface->getTenantByUuid($uuid);
    }

    public function paginate(int $page, int $totalPerPage, string $filter):PaginateRepositoryInterface
    {
        return $this->tenantRepositoryInterface->paginate(page: $page, totalPrePage: $totalPerPage, filter:  $filter);
    }
}
