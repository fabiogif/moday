<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateFinancialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:receita,despesa,ambos'],
            'applies_to_payable' => ['sometimes', 'boolean'],
            'applies_to_receivable' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'O nome deve ser um texto válido.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'type.in' => 'O tipo deve ser: receita, despesa ou ambos.',
            'color.regex' => 'A cor deve estar no formato hexadecimal (#RRGGBB).',
            'description.max' => 'A descrição não pode ter mais de 500 caracteres.',
            'is_active.boolean' => 'O campo ativo deve ser verdadeiro ou falso.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}
