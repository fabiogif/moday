<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProcessSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'            => 'required|integer|exists:plans,id',
            'token'              => 'required|string',
            'payment_method_id'  => 'required|string',
            'payment_type_id'    => 'required|string|in:credit_card,debit_card',
            'transaction_amount' => 'required|numeric|min:0.01',
            'installments'       => 'required|integer|min:1|max:24',
            'payer_email'        => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required'            => 'O ID do plano é obrigatório.',
            'plan_id.exists'              => 'O plano selecionado não existe.',
            'token.required'              => 'O token do cartão é obrigatório.',
            'payment_method_id.required'  => 'A bandeira do cartão é obrigatória.',
            'payment_type_id.required'    => 'O tipo de pagamento é obrigatório.',
            'payment_type_id.in'          => 'O tipo de pagamento deve ser credit_card ou debit_card.',
            'transaction_amount.required' => 'O valor da transação é obrigatório.',
            'transaction_amount.min'      => 'O valor mínimo é R$ 0,01.',
            'installments.required'       => 'O número de parcelas é obrigatório.',
            'payer_email.required'        => 'O e-mail do pagador é obrigatório.',
            'payer_email.email'           => 'O e-mail do pagador deve ser válido.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
