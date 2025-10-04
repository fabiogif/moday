<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\contracts\ProductRepositoryInterface;
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
        // Many-to-many join through category_product
        return $this->entity
            ->select('products.*', 'categories.name as name_category')
            ->leftJoin('category_product', 'category_product.product_id', '=', 'products.id')
            ->leftJoin('categories', 'category_product.category_id', '=', 'categories.id')
            ->where('products.tenant_id', $idTenant)
            ->where('categories.tenant_id', $idTenant)
            ->when(!empty($categories), function ($query) use ($categories) {
                $query->whereIn('categories.uuid', $categories);
            })
            ->groupBy('products.id', 'categories.name')
            ->orderBy('products.created_at', 'desc')
            ->get();
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
        $totalProducts = $this->entity->where('tenant_id', $tenantId)->count();
        
        // Calcular receita total (soma de todos os preços dos produtos)
        $totalRevenue = $this->entity->where('tenant_id', $tenantId)->sum('price');
        
        // Produtos ativos (assumindo que produtos com qtd_stock > 0 são ativos)
        $activeProducts = $this->entity->where('tenant_id', $tenantId)
            ->where('qtd_stock', '>', 0)
            ->count();
        
        // Estoque baixo (produtos com qtd_stock <= 3)
        $lowStockProducts = $this->entity->where('tenant_id', $tenantId)
            ->where('qtd_stock', '<=', 3)
            ->count();
        
        return [
            'total_products' => $totalProducts,
            'total_revenue' => $totalRevenue,
            'active_products' => $activeProducts,
            'low_stock_products' => $lowStockProducts
        ];
    }
}
