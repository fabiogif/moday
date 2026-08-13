<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'icon' => 'required|string|max:50',
            'is_initial' => 'sometimes|boolean',
            'is_final' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do status é obrigatório',
            'name.max' => 'O nome deve ter no máximo :max caracteres',
            'color.required' => 'A cor é obrigatória',
            'color.regex' => 'A cor deve estar no formato hexadecimal (#RRGGBB)',
            'icon.required' => 'O ícone é obrigatório',
            'icon.max' => 'O ícone deve ter no máximo :max caracteres',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $validator->errors()
        ], 422));
    }
}

