<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ActivateSubscriptionRequest extends FormRequest
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
            'plan_id' => 'required|integer|exists:plans,id',
            // Opcional: planos gratuitos não cobram; mantido para compatibilidade com clientes legados.
            'payment_method' => 'nullable|string',
            'payment_data' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'O ID do plano é obrigatório.',
            'plan_id.integer' => 'O ID do plano deve ser um número inteiro.',
            'plan_id.exists' => 'O plano selecionado não existe.',
            'payment_method.string' => 'O método de pagamento deve ser um texto válido.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $validator->errors(),
        ], 422));
    }
}


