<?php

namespace App\Services;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;

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

        // Processar opcionais se existirem
        if (isset($data['optionals'])) {
            $data['optionals'] = $this->processVariations($data['optionals']);
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

    /**
     * Get product by identifier (can be UUID or numeric ID)
     */
    public function getByIdentifier($identifier)
    {
        // Se é numérico, buscar por ID
        if (is_numeric($identifier)) {
            return $this->productRepositoryInterface->getById($identifier);
        }
        // Caso contrário, buscar por UUID
        return $this->productRepositoryInterface->getByUuid($identifier);
    }

    public function update(array $data, int $id)
    {
        // Processar variações se existirem
        if (isset($data['variations'])) {
            $data['variations'] = $this->processVariations($data['variations']);
        }
        
        // Processar opcionais se existirem
        if (isset($data['optionals'])) {
            $data['optionals'] = $this->processVariations($data['optionals']);
        }

        // Separar categories dos dados antes de atualizar
        $categories = $data['categories'] ?? null;
        unset($data['categories']);
        
        $product = $this->productRepositoryInterface->update($data, $id);
        
        // Atualizar categorias se fornecidas
        if ($product && $categories && !empty($categories)) {
            // Se categories é uma string, converter para array
            if (is_string($categories)) {
                $categories = json_decode($categories, true) ?? [$categories];
            }
            
            $categoryData = $this->getCategoryByProduct($categories);
            // Primeiro, remove todas as categorias existentes
            $this->productRepositoryInterface->detachAllCategories($id);
            // Depois, anexa as novas categorias
            $this->productRepositoryInterface->attachCategories($id, $categoryData);
        }
        
        // Invalidate cache after updating product
        if ($product) {
            // Buscar produto para pegar tenant_id
            $productModel = $this->productRepositoryInterface->getById($id);
            if ($productModel && $productModel->tenant_id) {
                $this->cacheService->invalidateProductCache($productModel->tenant_id);
            }
        }
        
        // Retornar o produto atualizado com relacionamentos
        return $this->productRepositoryInterface->getById($id);
    }

    public function delete(int $id)
    {
        // Get product before deletion to get tenant_id
        $product = $this->productRepositoryInterface->getById($id);
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

    /**
     * Produtos para PDV, novo pedido e demais fluxos de venda (sem inativos / sem estoque).
     */
    public function getCatalogProductsByTenantId(int $idTenant)
    {
        return $this->cacheService->getProductCatalogList($idTenant, function () use ($idTenant) {
            return $this->productRepositoryInterface->getCatalogProductsByTenant($idTenant, []);
        });
    }

    public function paginateProductsByTenant(int $tenantId, int $page, int $perPage, ?string $search = null)
    {
        $params = ['page' => $page, 'per_page' => $perPage, 'search' => $search];

        return $this->cacheService->getProductListPaginated(
            $tenantId,
            $params,
            fn () => $this->productRepositoryInterface->paginateForTenant($tenantId, $page, $perPage, $search)
        );
    }

    public function paginateCatalogProductsByTenant(int $tenantId, int $page, int $perPage, ?string $search = null)
    {
        $params = ['page' => $page, 'per_page' => $perPage, 'search' => $search];

        return $this->cacheService->getProductCatalogListPaginated(
            $tenantId,
            $params,
            fn () => $this->productRepositoryInterface->paginateCatalogForTenant($tenantId, $page, $perPage, $search)
        );
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

    public function findByCode(string $code, int $tenantId, bool $catalogOnly = true)
    {
        return $this->productRepositoryInterface->findByCode($code, $tenantId, $catalogOnly);
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
            // Novo formato: id, name, price
            if (is_array($variation) && 
                isset($variation['id']) && 
                isset($variation['name']) &&
                isset($variation['price']) &&
                !empty(trim($variation['name']))) {
                
                $cleanVariations[] = [
                    'id' => trim($variation['id']),
                    'name' => trim($variation['name']),
                    'price' => is_numeric($variation['price']) ? (float)$variation['price'] : 0
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
