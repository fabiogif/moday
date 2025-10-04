<?php

namespace App\Repositories\contracts;

interface PaymentMethodRepositoryInterface extends BaseRepositoryInterface
{
    public function createPaymentMethod(array $data);
    public function getPaymentMethodsByTenant($tenantId);
    public function getActivePaymentMethodsByTenant($tenantId);
    public function getPaymentMethodByUuid($uuid);
    public function updatePaymentMethod($id, array $data);
    public function deletePaymentMethod($id);
    public function getPaymentMethodById($id);
}