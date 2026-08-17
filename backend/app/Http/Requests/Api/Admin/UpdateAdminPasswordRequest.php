<?php

namespace App\Http\Requests\Api\Admin;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAdminPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'A senha atual é obrigatória.',
            'password.required'         => 'A nova senha é obrigatória.',
            'password.min'              => 'A nova senha deve ter pelo menos 8 caracteres.',
            'password.confirmed'        => 'A confirmação da senha não confere.',
            'password_confirmation.required' => 'A confirmação da senha é obrigatória.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}
