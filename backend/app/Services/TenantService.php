<?php

namespace App\Services;

use App\Models\Plan;
use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;

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

        $tenant = $this->plan->tenants()->create($data);
        
        // Garantir que o slug seja único se não foi fornecido
        if (empty($tenant->slug)) {
            $baseSlug = \Illuminate\Support\Str::slug($tenant->name);
            $slug = $baseSlug;
            $counter = 1;
            
            while (\App\Models\Tenant::where('slug', $slug)->where('id', '!=', $tenant->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $tenant->update(['slug' => $slug]);
        }
        
        return $tenant;
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

    public function update(string $uuid, array $data)
    {
        $tenant = $this->getTenantByUuid($uuid);
        
        if (!$tenant) {
            return null;
        }

        // Se o nome foi alterado, atualizar o slug
        if (isset($data['name']) && $data['name'] !== $tenant->name) {
            $baseSlug = \Illuminate\Support\Str::slug($data['name']);
            $slug = $baseSlug;
            $counter = 1;
            
            while (\App\Models\Tenant::where('slug', $slug)->where('id', '!=', $tenant->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $data['slug'] = $slug;
        }

        // Remover flag de remoção de logo dos dados
        $removeLogo = isset($data['remove_logo']) && $data['remove_logo'];
        unset($data['remove_logo']);

        // Se marcado para remover logo
        if ($removeLogo) {
            // Deletar logo antigo se existir
            if ($tenant->logo && \Storage::exists($tenant->logo)) {
                \Storage::delete($tenant->logo);
            }
            $data['logo'] = null;
        }

        $tenant->update($data);
        
        return $tenant->fresh();
    }
}
