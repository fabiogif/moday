<?php

namespace App\Repositories;

use App\Models\PaymentMethod;
use App\Repositories\contracts\PaymentMethodRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodRepository extends BaseRepository implements PaymentMethodRepositoryInterface
{
    public function __construct(protected Model $entity = new PaymentMethod())
    {
    }

    public function createPaymentMethod(array $data)
    {
        return $this->entity->create($data);
    }

    public function getPaymentMethodsByTenant($tenantId)
    {
        return $this->entity->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();
    }

    public function getActivePaymentMethodsByTenant($tenantId)
    {
        return $this->entity->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getPaymentMethodByUuid($uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function getPaymentMethodById($id)
    {
        return $this->entity->find($id);
    }

    public function updatePaymentMethod($id, array $data)
    {
        $paymentMethod = $this->entity->find($id);
        if (!$paymentMethod) {
            return null;
        }
        
        $paymentMethod->update($data);
        return $paymentMethod->fresh();
    }

    public function deletePaymentMethod($id)
    {
        $paymentMethod = $this->entity->find($id);
        if (!$paymentMethod) {
            return false;
        }
        
        return $paymentMethod->delete();
    }
}