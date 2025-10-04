<?php

namespace App\Services;

use App\Repositories\contracts\PaginateRepositoryInterface;
use App\Repositories\contracts\PlanRepositoryInterface;

readonly class PlanService
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

    public function getByUrl(string $url)
    {
        return $this->planRepositoryInterface->getByUrl($url);
    }
    public function paginate(int $page, int $totalPerPage, string $filter):PaginateRepositoryInterface
    {
        return $this->planRepositoryInterface->paginate(page: $page, totalPrePage: $totalPerPage, filter:  $filter);
    }
}
