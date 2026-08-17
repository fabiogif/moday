<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Repositories\Contracts\PublicStoreRepositoryInterface;

class PublicStoreRepository implements PublicStoreRepositoryInterface
{
    /**
     * Get active tenant by slug
     */
    public function getTenantBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active products by tenant ID with categories
     */
    public function getActiveProducts(int $tenantId): array
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->visibleInCatalog()
            ->with('categories')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get active payment methods by tenant ID
     */
    public function getActivePaymentMethods(int $tenantId): array
    {
        return PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}

