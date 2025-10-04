<?php

namespace App\Services;

use App\Repositories\contracts\PlanRepositoryInterface;

readonly class DetailPlanService
{
    public function __construct(private PlanRepositoryInterface $planRepositoryInterface)
    {}

    public function index()
    {
        return $this->planRepositoryInterface->index();
    }

    public function store(array $data)
    {
        return $this->planRepositoryInterface->store($data);
    }

    public function getById(int $id)
    {
        return $this->planRepositoryInterface->getById($id);
    }

    public function update(array $data, int $id)
    {
        return $this->planRepositoryInterface->update($data, $id);
    }

    public function delete(int $id)
    {
        return $this->planRepositoryInterface->delete($id);
    }

}
