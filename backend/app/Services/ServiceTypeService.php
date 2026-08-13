<?php

namespace App\Services;

use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\ServiceTypeRepositoryInterface;
use Illuminate\Support\Facades\Cache;

readonly class ServiceTypeService
{
    public function __construct(
        protected ServiceTypeRepositoryInterface $serviceTypeRepositoryInterface,
        protected CacheService $cacheService
    ) {}

    public function store(array $data)
    {
        $result = $this->serviceTypeRepositoryInterface->store($data);
        // Limpar cache após criar
        $this->invalidateServiceTypeCache();
        return $result;
    }

    public function update(array $data, int $id)
    {
        $result = $this->serviceTypeRepositoryInterface->update($data, $id);
        // Limpar cache após atualizar
        $this->invalidateServiceTypeCache();
        return $result;
    }

    public function delete(string $identify)
    {
        $result = $this->serviceTypeRepositoryInterface->delete($identify);
        // Limpar cache após deletar
        $this->invalidateServiceTypeCache();
        return $result;
    }

    public function getServiceTypeByUuid(string $uuid)
    {
        return $this->serviceTypeRepositoryInterface->getServiceTypeByUuid($uuid);
    }

    public function getServiceTypeByIdentify(string $identify)
    {
        return $this->serviceTypeRepositoryInterface->getServiceTypeByIdentify($identify);
    }

    public function getServiceTypeBySlug(string $slug)
    {
        return $this->serviceTypeRepositoryInterface->getServiceTypeBySlug($slug);
    }

    public function getActiveServiceTypes()
    {
        return $this->cacheService->remember('service_types_active', 3600, function () {
            return $this->serviceTypeRepositoryInterface->getActiveServiceTypes();
        });
    }

    public function getMenuServiceTypes()
    {
        return $this->cacheService->remember('service_types_menu', 3600, function () {
            return $this->serviceTypeRepositoryInterface->getMenuServiceTypes();
        });
    }

    public function paginate(int $page, int $totalPerPage, string $filter): PaginateRepositoryInterface
    {
        return $this->serviceTypeRepositoryInterface->paginate(
            page: $page,
            totalPrePage: $totalPerPage,
            filter: $filter
        );
    }

    /**
     * Limpa o cache relacionado a tipos de atendimento
     */
    private function invalidateServiceTypeCache(): void
    {
        Cache::forget('service_types_active');
        Cache::forget('service_types_menu');
    }
}

