<?php

namespace App\Repositories\Contracts;

interface  ProductRepositoryInterface extends BaseRepositoryInterface
{
   // public function index();
    public function getByUuid($identify);
    public function store(array $data);
  //  public function update(array $data, $id);
    public function delete($id, int $tenantId = null);
    public function getProductsByTenantUuid(int $idTenant, array $categories);

    /**
     * Produtos disponíveis para venda (ativos e com estoque) — PDV e cardápio interno.
     */
    public function getCatalogProductsByTenant(int $idTenant, array $categories = []);

    public function paginateForTenant(int $tenantId, int $page, int $perPage, ?string $search = null);

    public function paginateCatalogForTenant(int $tenantId, int $page, int $perPage, ?string $search = null);

    public function attachCategories(int $productId, array $categories);
    public function getStats(int $tenantId): array;
    public function findBySku(string $sku, int $tenantId): ?\App\Models\Product;

    public function findByBarcode(string $barcode, int $tenantId): ?\App\Models\Product;

    /**
     * Busca produto por código de barras ou SKU (match exato, com variantes EAN).
     */
    public function findByCode(string $code, int $tenantId, bool $catalogOnly = true): ?\App\Models\Product;
    
    /**
     * Buscar IDs internos dos produtos pelos UUIDs
     * 
     * @param int $tenantId
     * @param array $productUuids Array de UUIDs dos produtos
     * @return array Array de IDs internos
     */
    public function getProductIdsByUuids(int $tenantId, array $productUuids): array;
    //public function getProductsByTenantUuid(string $identify);

}
