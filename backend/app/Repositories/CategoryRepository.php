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
                $query->where('name', 'like', "%{$filter}%");
            }
        })->get()->toArray();
    }
    
    public function store(array $data)
    {
        $tenantId = $data['tenant_id'] ?? null;
        $name = $data['name'] ?? null;

        if ($tenantId && $name) {
            $existing = $this->entity->where('tenant_id', $tenantId)
                ->where('name', $name)
                ->where('status', 'I')
                ->first();

            if ($existing) {
                $payload = array_intersect_key($data, array_flip(['name', 'description', 'url']));
                $payload['status'] = 'A';

                if (empty($payload['url'])) {
                    $payload['url'] = \Illuminate\Support\Str::slug($payload['name'] ?? $existing->name);
                }

                $existing->update($payload);

                return $existing->fresh();
            }
        }

        return $this->entity->create($data);
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
        $result = $this->entity->where(function($query) use($filter, $tenantId) {
            if($filter) {
                $query->where('name', 'like', "%{$filter}%");
            }
            $query->where('tenant_id', $tenantId);
        })
            ->orderByDesc('id')
            ->paginate(perPage: $totalPerPage, columns: ['*'], pageName:'page', page: $page, total: null);
        return new PaginatePresenter($result);
    }
    
    public function updateByTenant(array $data, string|int $id, int $tenantId)
    {
        $query = $this->entity->where('tenant_id', $tenantId);

        if (is_numeric($id)) {
            $query->where('id', (int) $id);
        } else {
            $query->where('uuid', (string) $id);
        }

        $category = $query->first();

        if (!$category) {
            return null;
        }

        // Evita mass-assignment de campos inválidos do frontend
        $payload = array_intersect_key($data, array_flip(['name', 'description', 'url', 'status']));

        if (isset($payload['name']) && empty($payload['url'])) {
            $payload['url'] = \Illuminate\Support\Str::slug($payload['name']);
        }

        $category->update($payload);

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

        return (bool) $category->update([
            'status' => 'I',
        ]);
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
