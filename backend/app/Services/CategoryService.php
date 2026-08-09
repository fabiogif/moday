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

    public function store(array $data, int $tenantId = null)
    {
        $category = $this->categoryRepositoryInterface->store($data);
        if($tenantId) {
            $this->cacheService->invalidateCategoryCache($tenantId);
        }
        return $category;
    }

    public function getByUuid(string $identify, int $tenantId = null)
    {
        if($tenantId) {
            return $this->categoryRepositoryInterface->getByUuidAndTenant($identify, $tenantId);
        }
        return $this->categoryRepositoryInterface->getByUuid($identify);
    }

    public function update(array $data, string $identify, int $tenantId = null)
    {
        if($tenantId) {
            $category = $this->categoryRepositoryInterface->updateByTenant($data, $identify, $tenantId);
            $this->cacheService->invalidateCategoryCache($tenantId);
            return $category;
        }
        return $this->categoryRepositoryInterface->update($data, $identify);
    }

    public function delete(string $identify, int $tenantId = null)
    {
        if($tenantId) {
            $result = $this->categoryRepositoryInterface->deleteByTenant($identify, $tenantId);
            $this->cacheService->invalidateCategoryCache($tenantId);
            return $result;
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
