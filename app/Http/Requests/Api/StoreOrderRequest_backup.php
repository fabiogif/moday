<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseRequest;

class StoreOrderRequestBackup extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'token_company' => ['required', 'string'],
            'client_id' => ['nullable', 'string'],
            'table' => ['nullable', 'string'],
            'comment' => ['nullable', 'max:1000'],
            'products' => ['required', 'array'],
            'products.*.identify' => ['required', 'string'],
            'products.*.qty' => ['required', 'integer', 'min:1'],
            
            // Delivery validation
            'is_delivery' => ['boolean'],
            'use_client_address' => ['boolean'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'delivery_city' => ['nullable', 'string', 'max:100'],
            'delivery_state' => ['nullable', 'string', 'max:2'],
            'delivery_zip_code' => ['nullable', 'string', 'max:10'],
            'delivery_neighborhood' => ['nullable', 'string', 'max:100'],
            'delivery_number' => ['nullable', 'string', 'max:20'],
            'delivery_complement' => ['nullable', 'string', 'max:100'],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Simplified validation without complex tenant checks
     */
    public function withValidator($validator)
    {
        $validator->after(function($validator) {
            // Just basic validation - let the service handle the business logic
            
            // Se é delivery e não usa endereço do cliente, deve ter endereço de entrega
            if ($this->input('is_delivery') && !$this->input('use_client_address')) {
                if (empty($this->input('delivery_address')) || empty($this->input('delivery_city'))) {
                    $validator->errors()->add('delivery_address', 'Endereço de entrega é obrigatório quando não usar endereço do cliente.');
                }
            }

            // Se não é delivery, deve ter mesa
            if (!$this->input('is_delivery') && empty($this->input('table'))) {
                $validator->errors()->add('table', 'Mesa é obrigatória para pedidos que não são delivery.');
            }
        });
    }
}