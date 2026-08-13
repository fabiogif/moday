<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;

interface PublicStoreRepositoryInterface
{
    /**
     * Get active tenant by slug
     */
    public function getTenantBySlug(string $slug): ?Tenant;

    /**
     * Get active products by tenant ID
     */
    public function getActiveProducts(int $tenantId): array;

    /**
     * Get active payment methods by tenant ID
     */
    public function getActivePaymentMethods(int $tenantId): array;
}

