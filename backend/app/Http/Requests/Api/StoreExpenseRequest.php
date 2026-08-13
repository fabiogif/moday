<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'financial_category_id' => ['nullable', 'integer', 'exists:financial_categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'payment_method_id' => ['nullable', 'uuid', 'exists:payment_methods,uuid'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['nullable', 'in:pendente,pago,cancelado'],
            'recurrence' => ['nullable', 'in:unico,mensal,trimestral,anual'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'A descrição da despesa é obrigatória.',
            'issue_date.required' => 'A data de emissão é obrigatória.',
            'due_date.required' => 'A data de vencimento é obrigatória.',
            'due_date.after_or_equal' => 'A data de vencimento deve ser igual ou posterior à data de emissão.',
            'amount.required' => 'O valor da despesa é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'financial_category_id.exists' => 'Categoria não encontrada.',
            'supplier_id.exists' => 'Fornecedor não encontrado.',
            'payment_method_id.exists' => 'Forma de pagamento não encontrada.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}

