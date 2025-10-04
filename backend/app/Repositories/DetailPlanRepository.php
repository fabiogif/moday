<?php

namespace App\Repositories;

use App\Models\DetailPlan;
use App\Repositories\contracts\DetailPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DetailPlanRepository extends BaseRepository implements DetailPlanRepositoryInterface
{
    public function __construct(protected Model $entity = new DetailPlan())
    {
    }

}
