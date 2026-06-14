<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(protected Model $entity =  new Product())
    {
    }

    public function index(string $filter = null, int $tenantId = null): array
    {
        $query = $this->entity->with('categories');
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        if ($filter) {
            $query->where('name', 'like', "%{$filter}%");
        }
        
        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

     public function getByUuid($identify)
     {
         return $this->entity->where('uuid', $identify)->first();
     }
     public function store(array $data)
     {
        return $this->entity->create($data);
     }

//     public function update(array $data, $id)
//     {
//        return $this->entity->whereId($id)->update($data);
//     }

     public function delete($id, int $tenantId = null)
     {
         $query = $this->entity->where('id', $id);
         if($tenantId) {
             $query->where('tenant_id', $tenantId);
         }
         return $query->delete();
     }

    public function getProductsByTenantUuid(int $idTenant, array $categories)
    {
        return $this->buildTenantProductsQuery($idTenant, $categories)
            ->orderBy('products.created_at', 'desc')
            ->get();
    }

    public function getCatalogProductsByTenant(int $idTenant, array $categories = [])
    {
        return $this->buildTenantProductsQuery($idTenant, $categories)
            ->visibleInCatalog()
            ->orderBy('products.name')
            ->get();
    }

    private function buildTenantProductsQuery(int $idTenant, array $categories)
    {
        $query = $this->entity->newQuery()
            ->where('products.tenant_id', $idTenant);

        if (!empty($categories)) {
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->whereIn('categories.uuid', $categories);
            });
        }

        return $query->with('categories');
    }
    public function attachCategories(int $productId, array $categories)
    {
        $product = $this->entity->find($productId);

        $productCategory = array();

        foreach($categories as $category){
            array_push($productCategory, [
                'product_id' => $productId,
                'category_id' => $category['category_id']
            ]);
        }

        $product->categories()->attach($productCategory);
    }

    public function detachAllCategories(int $productId)
    {
        $product = $this->entity->find($productId);
        if ($product) {
            $product->categories()->detach();
        }
    }

    public function getStats(int $tenantId): array
    {
        $total = $this->entity->where('tenant_id', $tenantId)->count();
        
        // Produtos ativos (is_active = true)
        $active = $this->entity->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
        
        // Produtos inativos (is_active = false)
        $inactive = $this->entity->where('tenant_id', $tenantId)
            ->where('is_active', false)
            ->count();
        
        // Produtos sem estoque (qtd_stock <= 0)
        $outOfStock = $this->entity->where('tenant_id', $tenantId)
            ->where('qtd_stock', '<=', 0)
            ->count();
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'out_of_stock' => $outOfStock
        ];
    }

    public function findBySku(string $sku, int $tenantId): ?Product
    {
        return $this->entity->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('sku', $sku)
            ->first();
    }

    public function getProductIdsByUuids(int $tenantId, array $productUuids): array
    {
        return $this->entity->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereIn('uuid', $productUuids)
            ->pluck('id')
            ->toArray();
    }
}
