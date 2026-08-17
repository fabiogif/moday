<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Concerns\SearchesFullText;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    use SearchesFullText;

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
            $this->applyFullTextSearch($query, ['name'], $filter);
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
         $query = $this->entity->newQuery()->where('id', $id);
         if ($tenantId) {
             $query->where('tenant_id', $tenantId);
         }

         /** @var Product|null $product */
         $product = $query->first();
         if (!$product) {
             return false;
         }

         // Usa delete() do model para disparar SoftDeletes + eventos (libera barcode).
         return (bool) $product->delete();
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

    private function applyProductSearch($query, ?string $search)
    {
        if ($search === null || $search === '') {
            return $query;
        }

        return $this->applyFullTextSearch(
            $query,
            ['products.name', 'products.brand'],
            $search,
            ['products.sku', 'products.barcode']
        );
    }

    public function paginateForTenant(int $tenantId, int $page, int $perPage, ?string $search = null, ?string $filter = null)
    {
        $query = $this->applyProductSearch($this->buildTenantProductsQuery($tenantId, []), $search)
            ->orderBy('products.created_at', 'desc');

        $this->applyListFilter($query, $filter);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Filtros rápidos do catálogo mobile: active | out_of_stock | promo.
     */
    private function applyListFilter($query, ?string $filter): void
    {
        if ($filter === null || $filter === '' || $filter === 'all') {
            return;
        }

        match ($filter) {
            'active' => $query->where('products.is_active', true),
            'out_of_stock' => $query->where('products.qtd_stock', '<=', 0),
            'promo' => $query->whereNotNull('products.promotional_price')
                ->where('products.promotional_price', '>', 0),
            default => null,
        };
    }

    public function paginateCatalogForTenant(int $tenantId, int $page, int $perPage, ?string $search = null)
    {
        $query = $this->applyProductSearch($this->buildTenantProductsQuery($tenantId, []), $search)
            ->visibleInCatalog()
            ->orderBy('products.name');

        return $query->paginate($perPage, ['*'], 'page', $page);
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

    public function findByBarcode(string $barcode, int $tenantId): ?Product
    {
        return $this->entity->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('barcode', $barcode)
            ->first();
    }

    public function findByCode(string $code, int $tenantId, bool $catalogOnly = true): ?Product
    {
        $variants = self::barcodeLookupVariants($code);
        if ($variants === []) {
            return null;
        }

        $query = $this->entity->newQuery()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($variants) {
                $q->whereIn('barcode', $variants)
                    ->orWhereIn('sku', $variants);
            });

        if ($catalogOnly) {
            $query->visibleInCatalog();
        }

        return $query->with('categories')->first();
    }

    /**
     * @return list<string>
     */
    public static function barcodeLookupVariants(string $code): array
    {
        $normalized = trim($code);
        $normalized = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized) ?? '';
        if ($normalized === '') {
            return [];
        }

        $variants = [$normalized];
        if (ctype_digit($normalized)) {
            $trimmed = ltrim($normalized, '0');
            if ($trimmed !== '') {
                $variants[] = $trimmed;
                $variants[] = str_pad($trimmed, 13, '0', STR_PAD_LEFT);
                $variants[] = str_pad($trimmed, 12, '0', STR_PAD_LEFT);
            }
        }

        return array_values(array_unique(array_filter($variants)));
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
