<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAccountPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'string', 'max:255'],
            'financial_category_id' => ['nullable', 'integer', 'exists:financial_categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'payment_method_id' => ['nullable', 'uuid', 'exists:payment_methods,uuid'],
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'payment_date' => ['nullable', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'interest' => ['nullable', 'numeric', 'min:0'],
            'fine' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:pendente,pago,parcial,cancelado,vencido'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'installment_number' => ['nullable', 'integer', 'min:1'],
            'total_installments' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.string' => 'A descrição deve ser um texto válido.',
            'description.max' => 'A descrição não pode ter mais de 255 caracteres.',
            'financial_category_id.exists' => 'Categoria financeira não encontrada.',
            'supplier_id.exists' => 'Fornecedor não encontrado.',
            'payment_method_id.exists' => 'Forma de pagamento não encontrada.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'status.in' => 'Status inválido. Use: pendente, pago, parcial, cancelado ou vencido.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}

