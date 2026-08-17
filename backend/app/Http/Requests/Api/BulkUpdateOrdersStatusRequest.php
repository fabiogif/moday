<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkUpdateOrdersStatusRequest extends FormRequest
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
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|string',
            'status' => 'required|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_ids.required' => 'É necessário informar pelo menos um pedido para atualização.',
            'order_ids.array' => 'Os IDs dos pedidos devem ser informados em um array.',
            'order_ids.min' => 'É necessário informar pelo menos um pedido para atualização.',
            'order_ids.*.required' => 'Cada ID de pedido é obrigatório.',
            'order_ids.*.string' => 'Cada ID de pedido deve ser uma string.',
            'status.required' => 'O status é obrigatório.',
            'status.string' => 'O status deve ser uma string.',
            'status.max' => 'O status deve ter no máximo :max caracteres.',
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

