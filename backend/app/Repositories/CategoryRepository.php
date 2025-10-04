<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(protected Model $entity =  new Category())
    {
    }
    
    public function index(string $filter = null): array
    {
        return $this->entity->where(function($query) use($filter) {
            if($filter) {
                $query->where('name', 'like', "%{$filter}%");
            }
        })->get()->toArray();
    }
    
    public function getByUuid(string $identify)
    {
        return $this->entity->where('uuid', $identify)->first();
    }
    
    public function getByUuidAndTenant(string $identify, int $tenantId)
    {
        return $this->entity->where('uuid', $identify)
                           ->where('tenant_id', $tenantId)
                           ->first();
    }
    
    public function paginateByTenant(int $page, int $totalPerPage, string $filter, int $tenantId)
    {
        $result = $this->entity->where(function($query) use($filter, $tenantId) {
            if($filter) {
                $query->where('name', 'like', "%{$filter}%");
            }
            $query->where('tenant_id', $tenantId);
        })->paginate(perPage: $totalPerPage, columns: ['*'], pageName:'page', page: $page, total: null);
        return new \App\Repositories\contracts\Presenter\PaginatePresenter($result);
    }
    
    public function updateByTenant(array $data, int $id, int $tenantId)
    {
        return $this->entity->where('id', $id)
                           ->where('tenant_id', $tenantId)
                           ->update($data);
    }
    
    public function deleteByTenant(string $identify, int $tenantId)
    {
        return $this->entity->where('uuid', $identify)
                           ->where('tenant_id', $tenantId)
                           ->update(['status' => 'I']);
    }

    public function getStats(int $tenantId): array
    {
        $totalCategories = $this->entity->where('tenant_id', $tenantId)->count();
        $activeCategories = $this->entity->where('tenant_id', $tenantId)->where('status', 'A')->count();
        $inactiveCategories = $this->entity->where('tenant_id', $tenantId)->where('status', 'I')->count();
        
        // Calcular produtos por categoria
        $categoriesWithProducts = $this->entity->where('tenant_id', $tenantId)
            ->withCount('products')
            ->get();
        
        $totalProducts = $categoriesWithProducts->sum('products_count');
        $avgProductsPerCategory = $totalCategories > 0 ? round($totalProducts / $totalCategories, 1) : 0;
        
        return [
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'inactive_categories' => $inactiveCategories,
            'avg_products_per_category' => $avgProductsPerCategory,
            'total_products' => $totalProducts
        ];
    }
}
