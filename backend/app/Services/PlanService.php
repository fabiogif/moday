<?php

namespace App\Services;

use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;

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

    public function getById(int|string $id)
    {
        return $this->planRepositoryInterface->getById((int) $id);
    }

    public function update(array $data, int|string $id)
    {
        return $this->planRepositoryInterface->update($data, (int) $id);
    }

    public function delete(int|string $id)
    {
        return $this->planRepositoryInterface->delete((int) $id);
    }

    public function getByUrl(string $url)
    {
        return $this->planRepositoryInterface->getByUrl($url);
    }
    public function paginate(int $page, int $totalPerPage, string $filter):PaginateRepositoryInterface
    {
        return $this->planRepositoryInterface->paginate(page: $page, totalPrePage: $totalPerPage, filter:  $filter);
    }

    public function urlExists(string $url, ?int $excludeId = null): bool
    {
        return $this->planRepositoryInterface->urlExists($url, $excludeId);
    }

    public function storeWithDetails(array $data, array $details = []): mixed
    {
        unset($data['details']);
        $plan = $this->planRepositoryInterface->store($data);

        if ($plan && !empty($details)) {
            return $this->planRepositoryInterface->syncDetails($plan->id, $details);
        }

        return $plan?->load('details');
    }

    public function updateWithDetails(array $data, int|string $id, array $details = []): mixed
    {
        unset($data['details']);
        $this->planRepositoryInterface->update($data, (int) $id);

        if (isset($details)) {
            return $this->planRepositoryInterface->syncDetails((int) $id, $details);
        }

        return $this->planRepositoryInterface->getById((int) $id);
    }

    public function getActivePlansWithDetails()
    {
        return $this->planRepositoryInterface->getActivePlansWithDetails();
    }
}
