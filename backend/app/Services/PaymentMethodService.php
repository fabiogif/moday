<?php

namespace App\Services;

use App\Repositories\contracts\PaymentMethodRepositoryInterface;
use App\Repositories\contracts\TenantRepositoryInterface;

readonly class PaymentMethodService
{
    public function __construct(
        protected PaymentMethodRepositoryInterface $paymentMethodRepositoryInterface,
        protected TenantRepositoryInterface $tenantRepositoryInterface,
        protected CacheService $cacheService
    ) {
    }

    public function createPaymentMethod(array $data, int $tenantId)
    {
        // Adicionar tenant_id aos dados
        $data['tenant_id'] = $tenantId;
        
        $paymentMethod = $this->paymentMethodRepositoryInterface->createPaymentMethod($data);
        
        // Invalidar cache
        $this->cacheService->invalidatePaymentMethodCache($tenantId);
        
        return $paymentMethod;
    }

    public function getPaymentMethodsByTenant(int $tenantId)
    {
        return $this->cacheService->getPaymentMethodList($tenantId, function () use ($tenantId) {
            return $this->paymentMethodRepositoryInterface->getPaymentMethodsByTenant($tenantId);
        });
    }

    public function getActivePaymentMethodsByTenant(int $tenantId)
    {
        return $this->cacheService->getActivePaymentMethodList($tenantId, function () use ($tenantId) {
            return $this->paymentMethodRepositoryInterface->getActivePaymentMethodsByTenant($tenantId);
        });
    }

    public function getPaymentMethodByUuid(string $uuid, int $tenantId)
    {
        $paymentMethod = $this->paymentMethodRepositoryInterface->getPaymentMethodByUuid($uuid);
        
        // Verificar se pertence ao tenant
        if ($paymentMethod && $paymentMethod->tenant_id !== $tenantId) {
            return null;
        }
        
        return $paymentMethod;
    }

    public function updatePaymentMethod(string $uuid, array $data, int $tenantId)
    {
        $paymentMethod = $this->getPaymentMethodByUuid($uuid, $tenantId);
        
        if (!$paymentMethod) {
            return null;
        }
        
        $updatedPaymentMethod = $this->paymentMethodRepositoryInterface->updatePaymentMethod($paymentMethod->id, $data);
        
        // Invalidar cache
        $this->cacheService->invalidatePaymentMethodCache($tenantId);
        
        return $updatedPaymentMethod;
    }

    public function deletePaymentMethod(string $uuid, int $tenantId)
    {
        $paymentMethod = $this->getPaymentMethodByUuid($uuid, $tenantId);
        
        if (!$paymentMethod) {
            return false;
        }
        
        $deleted = $this->paymentMethodRepositoryInterface->deletePaymentMethod($paymentMethod->id);
        
        // Invalidar cache
        if ($deleted) {
            $this->cacheService->invalidatePaymentMethodCache($tenantId);
        }
        
        return $deleted;
    }
}