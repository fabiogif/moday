<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreFinancialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:receita,despesa,ambos'],
            'applies_to_payable' => ['nullable', 'boolean'],
            'applies_to_receivable' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $payable = $this->boolean('applies_to_payable');
            $receivable = $this->boolean('applies_to_receivable');
            $type = $this->input('type');

            if (!$payable && !$receivable && !$type) {
                $validator->errors()->add(
                    'applies_to_receivable',
                    'Selecione ao menos Contas a Pagar ou Contas a Receber.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da categoria é obrigatório.',
            'type.in' => 'O tipo deve ser: receita, despesa ou ambos.',
            'color.regex' => 'A cor deve estar no formato hexadecimal (#RRGGBB).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}
