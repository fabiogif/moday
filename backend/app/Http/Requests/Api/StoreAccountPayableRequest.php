<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAccountPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'financial_category_id' => ['required', 'integer', 'exists:financial_categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'payment_method_id' => ['nullable', 'uuid', 'exists:payment_methods,uuid'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'interest' => ['nullable', 'numeric', 'min:0'],
            'fine' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:pendente,pago,parcial,cancelado,vencido'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'installment_number' => ['nullable', 'integer', 'min:1'],
            'total_installments' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'A descrição é obrigatória.',
            'description.max' => 'A descrição não pode ter mais de 255 caracteres.',
            'financial_category_id.required' => 'A categoria financeira é obrigatória.',
            'financial_category_id.exists' => 'Categoria financeira não encontrada.',
            'supplier_id.exists' => 'Fornecedor não encontrado.',
            'payment_method_id.exists' => 'Forma de pagamento não encontrada.',
            'issue_date.required' => 'A data de emissão é obrigatória.',
            'issue_date.date' => 'A data de emissão deve ser uma data válida.',
            'due_date.required' => 'A data de vencimento é obrigatória.',
            'due_date.date' => 'A data de vencimento deve ser uma data válida.',
            'due_date.after_or_equal' => 'A data de vencimento deve ser igual ou posterior à data de emissão.',
            'payment_date.date' => 'A data de pagamento deve ser uma data válida.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'amount_paid.numeric' => 'O valor pago deve ser numérico.',
            'amount_paid.min' => 'O valor pago não pode ser negativo.',
            'discount.numeric' => 'O desconto deve ser numérico.',
            'discount.min' => 'O desconto não pode ser negativo.',
            'interest.numeric' => 'Os juros devem ser numéricos.',
            'interest.min' => 'Os juros não podem ser negativos.',
            'fine.numeric' => 'A multa deve ser numérica.',
            'fine.min' => 'A multa não pode ser negativa.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido. Use: pendente, pago, parcial, cancelado ou vencido.',
            'document_number.max' => 'O número do documento não pode ter mais de 100 caracteres.',
            'notes.max' => 'As observações não podem ter mais de 1000 caracteres.',
            'installment_number.integer' => 'O número da parcela deve ser um número inteiro.',
            'installment_number.min' => 'O número da parcela deve ser no mínimo 1.',
            'total_installments.integer' => 'O total de parcelas deve ser um número inteiro.',
            'total_installments.min' => 'O total de parcelas deve ser no mínimo 1.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}

