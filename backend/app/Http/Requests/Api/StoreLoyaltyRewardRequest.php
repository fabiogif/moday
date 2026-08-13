<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLoyaltyRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:discount_percentage,discount_fixed,free_product,free_shipping,custom'],
            'points_required' => ['required', 'integer', 'min:1'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'max_redemptions_per_user' => ['nullable', 'integer', 'min:1'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da recompensa é obrigatório.',
            'type.required' => 'Selecione o tipo de recompensa.',
            'points_required.required' => 'Defina quantos pontos são necessários.',
            'available_until.after_or_equal' => 'A data final deve ser posterior à data inicial.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}

