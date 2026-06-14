<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseRequest;

class OfflineSyncRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'orders'                         => ['required', 'array', 'min:1', 'max:50'],
            'orders.*.offline_id'            => ['required', 'string'],
            'orders.*.client_id'             => ['required', 'integer'],
            'orders.*.payment_method_id'     => ['required', 'integer'],
            'orders.*.total'                 => ['required', 'numeric', 'min:0'],
            'orders.*.products'              => ['required', 'array', 'min:1'],
            'orders.*.products.*.product_id' => ['required', 'integer'],
            'orders.*.products.*.quantity'   => ['required', 'integer', 'min:1'],
            'orders.*.products.*.price'      => ['required', 'numeric', 'min:0'],
        ];
    }
}
