<?php

namespace App\Services;

use App\Repositories\contracts\{CategoryRepositoryInterface, PaginateRepositoryInterface};

readonly class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepositoryInterface,
        protected CacheService $cacheService
    )
    {}

    public function index()
    {
        return $this->categoryRepositoryInterface->index();
    }

    public function store(array $data)
    {
        return $this->categoryRepositoryInterface->store($data);
    }

    public function getByUuid(string $identify, int $tenantId = null)
    {
        if($tenantId) {
            return $this->categoryRepositoryInterface->getByUuidAndTenant($identify, $tenantId);
        }
        return $this->categoryRepositoryInterface->getByUuid($identify);
    }

    public function update(array $data, int $id, int $tenantId = null)
    {
        if($tenantId) {
            return $this->categoryRepositoryInterface->updateByTenant($data, $id, $tenantId);
        }
        return $this->categoryRepositoryInterface->update($data, $id);
    }

    public function delete(string $identify, int $tenantId = null)
    {
        if($tenantId) {
            return $this->categoryRepositoryInterface->deleteByTenant($identify, $tenantId);
        }
        return $this->categoryRepositoryInterface->delete($identify);
    }

    public function paginate(int $page, int $totalPerPage, string $filter, int $tenantId = null):PaginateRepositoryInterface
    {
        if($tenantId) {
            return $this->cacheService->getCategoryList($tenantId, function () use ($page, $totalPerPage, $filter, $tenantId) {
                return $this->categoryRepositoryInterface->paginateByTenant($page, $totalPerPage, $filter, $tenantId);
            });
        }
        return $this->categoryRepositoryInterface->paginate(page: $page, totalPrePage: $totalPerPage, filter:  $filter);
    }

    public function getStats(int $tenantId): array
    {
        return $this->categoryRepositoryInterface->getStats($tenantId);
    }

}
