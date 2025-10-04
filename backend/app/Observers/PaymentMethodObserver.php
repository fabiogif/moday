<?php

namespace App\Observers;

use App\Models\PaymentMethod;
use Illuminate\Support\Str;

class PaymentMethodObserver
{
    /**
     * Handle the PaymentMethod "creating" event.
     *
     * @param  \App\Models\PaymentMethod  $paymentMethod
     * @return void
     */
    public function creating(PaymentMethod $paymentMethod)
    {
        // Generate UUID if not provided
        if (empty($paymentMethod->uuid)) {
            $paymentMethod->uuid = Str::uuid();
        }
    }

    /**
     * Handle the PaymentMethod "saving" event.
     * Remove any flag field that might be set by mistake.
     *
     * @param  \App\Models\PaymentMethod  $paymentMethod
     * @return void
     */
    public function saving(PaymentMethod $paymentMethod)
    {
        // Ensure only allowed fields are being saved
        $allowedFields = ['uuid', 'name', 'description', 'tenant_id', 'is_active'];
        
        // Remove any attributes not in fillable array
        foreach ($paymentMethod->getAttributes() as $key => $value) {
            if (!in_array($key, $allowedFields) && !in_array($key, ['id', 'created_at', 'updated_at'])) {
                unset($paymentMethod->attributes[$key]);
            }
        }
    }
}