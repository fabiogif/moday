<?php

namespace App\Repositories\contracts;

interface  ProductRepositoryInterface extends BaseRepositoryInterface
{
   // public function index();
    public function getByUuid($identify);
    public function store(array $data);
  //  public function update(array $data, $id);
    public function delete($id, int $tenantId = null);
    public function getProductsByTenantUuid(int $idTenant, array $categories);
    public function attachCategories(int $productId, array $categories);
    public function getStats(int $tenantId): array;
    //public function getProductsByTenantUuid(string $identify);

}
