<?php

namespace App\Services;

use App\Repositories\contracts\CategoryRepositoryInterface;
use App\Repositories\contracts\PaginateRepositoryInterface;
use App\Repositories\contracts\ProductRepositoryInterface;
use App\Repositories\contracts\TenantRepositoryInterface;

readonly class ProductService
{

    public function __construct(
        private ProductRepositoryInterface $productRepositoryInterface,
        private TenantRepositoryInterface $tenantRepositoryInterface,
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        protected CacheService $cacheService
    )
    {}

    public function index()
    {
        $user = auth()->user();
        
        \Log::info('ProductService::index - Debug:', [
            'user_id' => $user ? $user->id : null,
            'tenant_id' => $user ? $user->tenant_id : null,
            'user_exists' => !!$user
        ]);
        
        if (!$user || !$user->tenant_id) {
            \Log::warning('ProductService::index - Usuário não autenticado ou sem tenant');
            return [];
        }
        
        return $this->cacheService->getProductList($user->tenant_id, function () use ($user) {
            $products = $this->productRepositoryInterface->getProductsByTenantUuid($user->tenant_id, []);
            \Log::info('ProductService::index - Produtos encontrados:', [
                'count' => $products ? $products->count() : 0,
                'tenant_id' => $user->tenant_id
            ]);
            return $products;
        });
    }

    public function store(array $data)
    {
        // Processar variações se existirem
        if (isset($data['variations'])) {
            $data['variations'] = $this->processVariations($data['variations']);
        }

        $store =  $this->productRepositoryInterface->store($data);

        // Verificar se categories existe e não é vazio
        if (isset($data['categories']) && !empty($data['categories'])) {
            // Se categories é uma string, converter para array
            if (is_string($data['categories'])) {
                $categories = json_decode($data['categories'], true) ?? [$data['categories']];
            } else {
                $categories = $data['categories'];
            }
            
            $category = $this->getCategoryByProduct($categories);
            $this->productRepositoryInterface->attachCategories($store->id, $category);
        }

        // Invalidate cache after creating product
        if ($store && isset($data['tenant_id'])) {
            $this->cacheService->invalidateProductCache($data['tenant_id']);
        }

        return $store;
    }

    public function getByUuid(string $identify)
    {
        return $this->productRepositoryInterface->getByUuid($identify);
    }

    public function update(array $data, int $id)
    {
        // Processar variações se existirem
        if (isset($data['variations'])) {
            $data['variations'] = $this->processVariations($data['variations']);
        }

        $product = $this->productRepositoryInterface->update($data, $id);
        
        // Atualizar categorias se fornecidas
        if ($product && isset($data['categories']) && !empty($data['categories'])) {
            // Se categories é uma string, converter para array
            if (is_string($data['categories'])) {
                $categories = json_decode($data['categories'], true) ?? [$data['categories']];
            } else {
                $categories = $data['categories'];
            }
            
            $categoryData = $this->getCategoryByProduct($categories);
            // Primeiro, remove todas as categorias existentes
            $this->productRepositoryInterface->detachAllCategories($product->id);
            // Depois, anexa as novas categorias
            $this->productRepositoryInterface->attachCategories($product->id, $categoryData);
        }
        
        // Invalidate cache after updating product
        if ($product && $product->tenant_id) {
            $this->cacheService->invalidateProductCache($product->tenant_id);
        }
        
        return $product;
    }

    public function delete(int $id)
    {
        // Get product before deletion to get tenant_id
        $product = $this->productRepositoryInterface->getByUuid($id);
        $tenantId = $product ? $product->tenant_id : null;
        
        $result = $this->productRepositoryInterface->delete($id);
        
        // Invalidate cache after deleting product
        if ($result && $tenantId) {
            $this->cacheService->invalidateProductCache($tenantId);
        }
        
        return $result;
    }

    public function getProductsByTenantId(int $idTenant)
    {
        return $this->cacheService->getProductList($idTenant, function () use ($idTenant) {
            return $this->productRepositoryInterface->getProductsByTenantUuid($idTenant, []);
        });
    }

    public function getProductsByTenantUuid(string $uuid, array $categories)
    {
        $tenant = $this->tenantRepositoryInterface->getTenantByUuid($uuid);
        return $this->productRepositoryInterface->getProductsByTenantUuid($tenant->id, $categories);
    }

    public function getStats(int $tenantId): array
    {
        return $this->cacheService->getProductStats($tenantId, function () use ($tenantId) {
            return $this->productRepositoryInterface->getStats($tenantId);
        });
    }

    private function getCategoryByProduct(array $categoryProduct): array
    {
        $categories = [];

        foreach ($categoryProduct as $item) {
            // Se item é uma string (UUID), usar diretamente
            if (is_string($item)) {
                $category = $this->categoryRepositoryInterface->getByUuid($item);
            } 
            // Se item é um array com uuid
            elseif (is_array($item) && isset($item['uuid'])) {
                $category = $this->categoryRepositoryInterface->getByUuid($item['uuid']);
            }
            // Se item é um array com id
            elseif (is_array($item) && isset($item['id'])) {
                $category = $this->categoryRepositoryInterface->getByUuid($item['id']);
            }
            else {
                continue; // Pular item inválido
            }
            
            if ($category) {
                array_push($categories, [
                    'category_id' => $category->id
                ]);
            }
        }
        return $categories;
    }

    /**
     * Process variations data to ensure consistent format
     * 
     * @param mixed $variations
     * @return array
     */
    private function processVariations($variations): array
    {
        if (is_string($variations)) {
            $decoded = json_decode($variations, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->validateVariationsStructure($decoded);
            }
            return [];
        }
        
        if (is_array($variations)) {
            return $this->validateVariationsStructure($variations);
        }
        
        return [];
    }

    /**
     * Validate and clean variations structure
     * 
     * @param array $variations
     * @return array
     */
    private function validateVariationsStructure(array $variations): array
    {
        $cleanVariations = [];
        
        foreach ($variations as $variation) {
            if (is_array($variation) && 
                isset($variation['type']) && 
                isset($variation['value']) &&
                !empty(trim($variation['type'])) &&
                !empty(trim($variation['value']))) {
                
                $cleanVariations[] = [
                    'type' => trim($variation['type']),
                    'value' => trim($variation['value'])
                ];
            }
            // Ignora silenciosamente variações vazias ou inválidas
        }
        
        return $cleanVariations;
    }
    public function paginate(int $page, int $totalPerPage, string $filter):PaginateRepositoryInterface
    {
        return $this->categoryRepositoryInterface->paginate(page: $page, totalPrePage: $totalPerPage, filter:  $filter);
    }

}
