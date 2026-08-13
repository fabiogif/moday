<?php

namespace App\Repositories;

use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\Presenter\PaginatePresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Models\{DetailPlan, Plan, Tenant};
use App\Repositories\Contracts\PlanRepositoryInterface;

class PlanRepository extends BaseRepository implements PlanRepositoryInterface
{
    public function __construct(protected Model $entity = new Plan())
    {
    }

     public function getById($id)
     {
        return $this->entity->where('id', $id)->first();
     }

    public function tenants()
    {
        return $this->entity->hasMany(Tenant::class);
    }

    public function getByUrl(string $urlPlan)
    {
        return $this->entity->where('url', $urlPlan)->first();
    }
    public function details()
    {
        return $this->entity->hasMany(DetailPlan::class);
    }

    public function getActivePlans(): Collection
    {
        return $this->entity->where('is_active', true)->get();
    }

    public function paginate(int $page = 1, int $totalPrePage = 15, string $filter = null): PaginateRepositoryInterface
    {
        $result = $this->entity->with('details')->where(function($query) use($filter) {
            if($filter) {
                $this->applyFullTextSearch($query, ['name'], $filter);
            }
        })->paginate(perPage: $totalPrePage, columns: ['*'], pageName:'page', page: $page, total: null);
        return new PaginatePresenter($result);
    }

    /**
     * Delete a plan by ID (hard delete)
     * Overrides BaseRepository::delete() which uses uuid and soft delete
     * 
     * @param string|int $identify Plan ID
     * @return bool
     */
    public function delete(string|int $identify)
    {
        $id = is_numeric($identify) ? (int) $identify : $identify;
        $plan = $this->entity->find($id);
        
        if (!$plan) {
            return false;
        }
        
        return $plan->delete();
    }

    /**
     * Update a plan by ID
     * 
     * @param array $data Data to update
     * @param string|int $id Plan ID
     * @return mixed
     */
    public function update(array $data, $id): mixed
    {
        $planId = is_numeric($id) ? (int) $id : $id;
        return $this->entity->where('id', $planId)->update($data);
    }

    public function getActivePlansWithDetails(): Collection
    {
        return $this->entity->where('is_active', true)
            ->with('details')
            ->orderBy('price', 'asc')
            ->get();
    }

    public function urlExists(string $url, ?int $excludeId = null): bool
    {
        $query = $this->entity->where('url', $url);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function syncDetails(int $planId, array $details): mixed
    {
        $plan = $this->entity->find($planId);
        if (!$plan) {
            return null;
        }

        $plan->details()->delete();

        foreach ($details as $detail) {
            $plan->details()->create([
                'name' => $detail['name'] ?? '',
                'description' => $detail['name'] ?? '',
            ]);
        }

        return $plan->load('details');
    }
}
