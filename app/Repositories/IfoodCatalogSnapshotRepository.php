<?php

namespace App\Repositories;

use App\Models\Integrations\Ifood\IfoodCatalogSnapshot;
use App\Repositories\Contracts\IfoodCatalogSnapshotRepositoryInterface;
use App\Repositories\Contracts\Presenter\PaginatePresenter;

class IfoodCatalogSnapshotRepository implements IfoodCatalogSnapshotRepositoryInterface
{
    public function __construct(
        private readonly IfoodCatalogSnapshot $model = new IfoodCatalogSnapshot()
    ) {
    }

    public function store(array $attributes): IfoodCatalogSnapshot
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function paginate(int $tenantId, array $filters = [], int $perPage = 20): \App\Repositories\Contracts\PaginateRepositoryInterface
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('captured_at');

        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        if (!empty($filters['catalog_id'])) {
            $query->where('catalog_id', $filters['catalog_id']);
        }

        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (!empty($filters['snapshot_type'])) {
            $query->where('snapshot_type', $filters['snapshot_type']);
        }

        return new PaginatePresenter($query->paginate($perPage));
    }
}

