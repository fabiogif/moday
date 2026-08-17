<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\Presenter\PaginatePresenter;
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
                $this->applyFullTextSearch($query, ['name'], $filter);
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
    
    public function paginateByTenant(int $page, int $totalPerPage, string $filter, int $tenantId): PaginateRepositoryInterface
    {
        $result = $this->entity->withCount('products')->where(function($query) use($filter, $tenantId) {
            if($filter) {
                $this->applyFullTextSearch($query, ['name'], $filter);
            }
            $query->where('tenant_id', $tenantId);
        })
            ->orderByDesc('id')
            ->paginate(perPage: $totalPerPage, columns: ['*'], pageName:'page', page: $page, total: null);
        return new PaginatePresenter($result);
    }
    
    public function updateByTenant(array $data, int $id, int $tenantId)
    {
        $category = $this->entity->where('id', $id)
                           ->where('tenant_id', $tenantId)
                           ->first();

        if (!$category) {
            return null;
        }

        $category->update($data);

        return $category->fresh();
    }
    
    public function deleteByTenant(string $identify, int $tenantId)
    {
        $category = $this->entity->where('uuid', $identify)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$category) {
            return false;
        }

        $category->update([
            'status' => 'I',
        ]);

        return $category->fresh();
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
