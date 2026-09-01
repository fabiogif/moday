<?php

namespace App\Services;

use App\Repositories\Contracts\{CategoryRepositoryInterface, PaginateRepositoryInterface};

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
        $tenantId = isset($data['tenant_id']) ? (int) $data['tenant_id'] : null;
        $name = isset($data['name']) ? trim((string) $data['name']) : null;

        // Soft-delete uses status=I: reactivate inactive category with same name.
        if ($tenantId && $name) {
            $inactive = \App\Models\Category::query()
                ->where('tenant_id', $tenantId)
                ->where('name', $name)
                ->where('status', 'I')
                ->first();

            if ($inactive) {
                $payload = array_intersect_key(
                    array_merge($this->syncActiveFlags($data), ['status' => 'A', 'is_active' => true]),
                    array_flip(['name', 'description', 'url', 'status', 'is_active'])
                );
                $category = $this->categoryRepositoryInterface->updateByTenant(
                    $payload,
                    (int) $inactive->id,
                    $tenantId
                );

                $this->cacheService->invalidateCategoryCache($tenantId);

                return $category;
            }
        }

        $data = $this->syncActiveFlags($data);
        if (!isset($data['status'])) {
            $data['status'] = 'A';
            $data['is_active'] = true;
        }

        $category = $this->categoryRepositoryInterface->store($data);

        if ($tenantId) {
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

    public function update(array $data, int $id, int $tenantId = null)
    {
        $data = $this->syncActiveFlags($data);
        if($tenantId) {
            $category = $this->categoryRepositoryInterface->updateByTenant($data, $id, $tenantId);
            $this->cacheService->invalidateCategoryCache($tenantId);
            return $category;
        }
        return $this->categoryRepositoryInterface->update($data, $id);
    }

    public function delete(string $identify, int $tenantId = null)
    {
        if($tenantId) {
            $deleted = $this->categoryRepositoryInterface->deleteByTenant($identify, $tenantId);
            $this->cacheService->invalidateCategoryCache($tenantId);
            return $deleted;
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

    public function getActiveByTenant(int $tenantId)
    {
        return $this->cacheService->getActiveCategoryList($tenantId, function () use ($tenantId) {
            return $this->categoryRepositoryInterface->getActiveByTenant($tenantId);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncActiveFlags(array $data): array
    {
        if (array_key_exists('isActive', $data)) {
            $data['is_active'] = (bool) $data['isActive'];
            $data['status'] = $data['is_active'] ? 'A' : 'I';
            unset($data['isActive']);
        } elseif (isset($data['status'])) {
            $data['is_active'] = $data['status'] === 'A';
        }

        return $data;
    }

}
