<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Plan, Tenant};
use App\Repositories\contracts\{TenantRepositoryInterface, PlanRepositoryInterface};
use Illuminate\Support\Str;

class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    protected Model $entity;
    public function __construct(protected Plan $plan,
                                protected PlanRepositoryInterface $planRepositoryInterface)
    {
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

    public function getTenantByUuid(string $uuid):Tenant|null
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function storeTenant(array $data)
    {
      return $this->entity->store($data);
    }
}
