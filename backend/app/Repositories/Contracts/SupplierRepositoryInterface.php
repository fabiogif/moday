<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupplierRepositoryInterface
{
    public function getAllByTenant(int $tenantId);

    public function paginateForTenant(int $tenantId, int $page, int $perPage, ?string $search = null): LengthAwarePaginator;

    public function getById(int $id);

    public function getByUuid(string $uuid);

    public function getByDocument(string $document);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    public function hasExpenses(int $id): bool;
}

