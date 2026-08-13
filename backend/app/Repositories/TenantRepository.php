<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\{Plan, Tenant};
use App\Repositories\Contracts\{TenantRepositoryInterface, PlanRepositoryInterface};
use App\Support\BrazilianDocuments;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    protected Model $entity;
    public function __construct(
        protected Plan $plan,
        protected PlanRepositoryInterface $planRepositoryInterface
    ) {
        $this->entity = new Tenant();
    }

//    public function storeTenant(array $data)
//    {
//        $plan = $this->planRepositoryInterface->getById($data['plan_id']);
//        $data = [
//            'name' => $data['name'],
//            'cnpj' => $data['cnpj'],
//            'url' => Str::kebab($data['name']),
//            'email' => $data['email'],
//            'subscription' => now(),
//            'expires_at' => now()->addDays(7)];
//
//        return   $this->plan->tenants()->create($data);
//    }

    public function getTenantByUuid(string $uuid): Tenant|null
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function storeTenant(array $data)
    {
        return $this->entity->store($data);
    }

    public function getTenantsWithPlan(?array $tenantIds = null, ?int $planId = null, ?string $status = null): Collection
    {
        $query = $this->entity->with('plan');

        if ($tenantIds) {
            $query->whereIn('id', $tenantIds);
        }

        if ($planId) {
            $query->where('plan_id', $planId);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->get();
    }

    public function getTenantBySlug(string $slug): ?Tenant
    {
        return $this->entity->where('slug', $slug)->first();
    }

    public function findById(int $id): ?Tenant
    {
        return $this->entity->find($id);
    }

    public function findActiveBySlug(string $slug): ?Tenant
    {
        return $this->entity->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    public function createRegisteredTenant(array $data, Plan $plan): Tenant
    {
        /** @var Tenant $tenant */
        $tenant = $this->entity->create([
            'name' => $data['company_name'],
            'email' => $data['company_email'] ?? $data['email'],
            'cnpj' => BrazilianDocuments::onlyAlphanumeric($data['company_cnpj'] ?? ''),
            'plan_id' => $plan->id,
            'subscription_plan' => $plan->name,
            'is_active' => true,
            'account_status' => $plan->isFree() ? 'active' : 'trial',
        ]);

        if ($plan->isFree()) {
            $tenant->activateFreePlan($plan->name);
        } else {
            $tenant->startTrial();
        }

        return $tenant->refresh();
    }
}
