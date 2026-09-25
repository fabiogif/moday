<?php

namespace App\Repositories\Contracts;

use App\Models\Integrations\Ifood\IfoodCatalogSnapshot;
use App\Repositories\Contracts\PaginateRepositoryInterface;

interface IfoodCatalogSnapshotRepositoryInterface
{
    public function store(array $attributes): IfoodCatalogSnapshot;

    public function paginate(int $tenantId, array $filters = [], int $perPage = 20): PaginateRepositoryInterface;
}

