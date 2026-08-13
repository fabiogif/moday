<?php

namespace App\Services;

use App\Repositories\Contracts\PublicStoreRepositoryInterface;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;

readonly class PublicStoreService
{
    public function __construct(
        private PublicStoreRepositoryInterface $repository
    ) {}

    /**
     * Get store information by slug
     */
    public function getStoreInfo(string $slug): ?array
    {
        try {
            $tenant = $this->repository->getTenantBySlug($slug);

            if (!$tenant) {
                return null;
            }

            return [
                'id' => $tenant->id,
                'uuid' => $tenant->uuid,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'state' => $tenant->state,
                'zipcode' => $tenant->zipcode,
                'logo' => ImageHelper::publicAssetPath($tenant->logo, 'logos'),
                'whatsapp' => $this->formatWhatsApp($tenant->phone),
                'settings' => $tenant->settings ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('PublicStoreService::getStoreInfo - Error:', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get store products by slug
     */
    public function getStoreProducts(string $slug): ?array
    {
        try {
            $tenant = $this->repository->getTenantBySlug($slug);

            if (!$tenant) {
                return null;
            }

            $products = $this->repository->getActiveProducts($tenant->id);

            // Process image URLs
            return array_map(function ($product) {
                $product['image'] = ImageHelper::publicAssetPath($product['image'] ?? null, 'products');
                
                // Format categories
                if (isset($product['categories']) && is_array($product['categories'])) {
                    $product['categories'] = array_map(function ($cat) {
                        return [
                            'uuid' => $cat['uuid'] ?? $cat['identify'] ?? null,
                            'name' => $cat['name'] ?? ''
                        ];
                    }, $product['categories']);
                }

                return $product;
            }, $products);
        } catch (\Exception $e) {
            Log::error('PublicStoreService::getStoreProducts - Error:', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get store payment methods by slug
     */
    public function getStorePaymentMethods(string $slug): ?array
    {
        try {
            $tenant = $this->repository->getTenantBySlug($slug);

            if (!$tenant) {
                return null;
            }

            return $this->repository->getActivePaymentMethods($tenant->id);
        } catch (\Exception $e) {
            Log::error('PublicStoreService::getStorePaymentMethods - Error:', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Resolve and return Tenant entity by slug (active only)
     */
    public function getTenantEntityBySlug(string $slug): ?Tenant
    {
        return $this->repository->getTenantBySlug($slug);
    }

    /**
     * Format WhatsApp phone number
     */
    private function formatWhatsApp(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if not present
        if (strlen($phone) === 11 || strlen($phone) === 10) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}

