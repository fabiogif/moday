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
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(perPage: $totalPerPage, columns: ['*'], pageName:'page', page: $page, total: null);
        return new PaginatePresenter($result);
    }

    public function getActiveByTenant(int $tenantId)
    {
        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where('status', 'A')
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }
    
    public function updateByTenant(array $data, int|string $id, int $tenantId)
    {
        $query = $this->entity->where('tenant_id', $tenantId);
        if (is_numeric($id)) {
            $query->where('id', (int) $id);
        } else {
            $query->where('uuid', $id);
        }
        $category = $query->first();

        if (!$category) {
            return null;
        }

        if (isset($data['name']) && empty($data['url'])) {
            $data['url'] = \Illuminate\Support\Str::slug($data['name']);
        }

        $category->update($data);

        return $category->fresh();
    }
    
    public function inactivateByTenant(string $identify, int $tenantId)
    {
        $category = $this->entity->where('uuid', $identify)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$category) {
            return false;
        }

        $category->update([
            'status' => 'I',
            'is_active' => false,
        ]);

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

        return (bool) $category->delete();
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

    public function updateOrder(int $tenantId, array $categoryUuids): bool
    {
        foreach ($categoryUuids as $index => $uuid) {
            $this->entity->where('uuid', $uuid)
                ->where('tenant_id', $tenantId)
                ->update(['order' => $index + 1]);
        }

        return true;
    }
}
