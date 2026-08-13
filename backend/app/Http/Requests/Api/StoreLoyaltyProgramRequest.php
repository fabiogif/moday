<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLoyaltyProgramRequest extends FormRequest
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
            'is_active' => ['nullable', 'boolean'],
            'points_per_currency' => ['required', 'numeric', 'min:0.01'],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'max_points_per_purchase' => ['nullable', 'numeric', 'min:0'],
            'points_expiry_days' => ['nullable', 'integer', 'min:1'],
            'excluded_categories' => ['nullable', 'array'],
            'excluded_categories.*' => ['integer', 'exists:categories,id'],
            'excluded_products' => ['nullable', 'array'],
            'excluded_products.*' => ['integer', 'exists:products,id'],
            'birthday_multiplier' => ['nullable', 'numeric', 'min:1'],
            'special_day_multipliers' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do programa é obrigatório.',
            'points_per_currency.required' => 'Defina quantos pontos por real gasto.',
            'points_per_currency.min' => 'Deve ser no mínimo 0.01 pontos por real.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}

