<?php

namespace App\Repositories;

use App\Repositories\contracts\PaginateRepositoryInterface;
use App\Repositories\contracts\Presenter\PaginatePresenter;
use Illuminate\Database\Eloquent\Model;
use App\Models\{DetailPlan, Plan, Tenant};
use App\Repositories\contracts\PlanRepositoryInterface;

class PlanRepository extends  BaseRepository implements PlanRepositoryInterface
{
    public function __construct(protected Model  $entity = new Plan())
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

    public function paginate(int $page = 1, int $totalPrePage = 15, string $filter = null): PaginateRepositoryInterface
    {
        $result = $this->entity->with('details')->where(function($query) use($filter) {
            if($filter) {
                $query->where('name' ,'like', "%{$filter}%");
            }
        })->paginate(perPage: $totalPrePage, columns: ['*'], pageName:'page', page: $page, total: null);
        return new PaginatePresenter($result);
    }


}
