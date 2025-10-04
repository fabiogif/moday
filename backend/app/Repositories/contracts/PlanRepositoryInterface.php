<?php

namespace App\Repositories\contracts;

interface PlanRepositoryInterface extends BaseRepositoryInterface
{
    public function tenants();
    public function getByUrl(string $urlPlan);

}
