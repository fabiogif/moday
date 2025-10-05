<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|required|string|in:Em Preparo,Pronto,Entregue,Cancelado',
            'comment' => 'nullable|string|max:500',
            'is_delivery' => 'sometimes|boolean',
            'use_client_address' => 'sometimes|boolean',
            'delivery_address' => 'nullable|string|max:255',
            'delivery_city' => 'nullable|string|max:100',
            'delivery_state' => 'nullable|string|max:2',
            'delivery_zip_code' => 'nullable|string|max:10',
            'delivery_neighborhood' => 'nullable|string|max:100',
            'delivery_number' => 'nullable|string|max:20',
            'delivery_complement' => 'nullable|string|max:100',
            'delivery_notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido. Valores permitidos: Em Preparo, Pronto, Entregue, Cancelado.',
            'comment.max' => 'O comentário deve ter no máximo :max caracteres.',
            'delivery_address.max' => 'O endereço deve ter no máximo :max caracteres.',
            'delivery_city.max' => 'A cidade deve ter no máximo :max caracteres.',
            'delivery_state.max' => 'O estado deve ter no máximo :max caracteres.',
            'delivery_zip_code.max' => 'O CEP deve ter no máximo :max caracteres.',
            'delivery_neighborhood.max' => 'O bairro deve ter no máximo :max caracteres.',
            'delivery_number.max' => 'O número deve ter no máximo :max caracteres.',
            'delivery_complement.max' => 'O complemento deve ter no máximo :max caracteres.',
            'delivery_notes.max' => 'As observações de entrega devem ter no máximo :max caracteres.',
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
            'errors' => $validator->errors()
        ], 422));
    }
}