<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CouponUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:40'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'discount_type' => ['sometimes', 'in:percentage,fixed'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_client' => ['nullable', 'integer', 'min:1'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.max' => 'O código pode ter no máximo 40 caracteres.',
            'discount_type.in' => 'Tipo de desconto inválido.',
            'discount_value.numeric' => 'Valor do desconto deve ser numérico.',
            'discount_value.min' => 'Valor do desconto deve ser positivo.',
            'max_discount_amount.min' => 'O teto de desconto deve ser positivo.',
            'minimum_order_amount.min' => 'O valor mínimo deve ser positivo.',
            'usage_limit.min' => 'O limite de uso deve ser maior que zero.',
            'usage_limit_per_client.min' => 'O limite por cliente deve ser maior que zero.',
            'end_at.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('code')) {
            $merge['code'] = strtoupper(trim((string) $this->input('code')));
        }

        foreach (['is_active', 'is_featured', 'remove_image'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $this->input($field);
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
