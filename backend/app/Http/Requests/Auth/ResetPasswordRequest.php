<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Classes\ApiResponseClass;

class ResetPasswordRequest extends FormRequest
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
            'token' => [
                'required',
                'string'
            ],
            'email' => [
                'required',
                'email',
                'max:255'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'token.required' => 'O campo token é obrigatório.',
            'token.string' => 'O campo token deve ser uma string.',
            
            'email.required' => 'O campo email é obrigatório.',
            'email.email' => 'O campo email deve ser um endereço válido.',
            'email.max' => 'O campo email não pode ter mais de 255 caracteres.',
            
            'password.required' => 'O campo senha é obrigatório.',
            'password.string' => 'O campo senha deve ser uma string.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     * Garante que sempre retorne JSON, mesmo se a requisição não especificar Accept: application/json
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}

