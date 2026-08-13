<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReorderOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => 'required|array',
            'order.*' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'order.required' => 'A nova ordem dos status é obrigatória.',
            'order.array' => 'A nova ordem dos status deve ser um array.',
            'order.*.required' => 'Cada item da ordem deve ser preenchido.',
            'order.*.string' => 'Cada item da ordem deve ser uma string (UUID ou identificador do status).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $validator->errors(),
        ], 422));
    }
}


