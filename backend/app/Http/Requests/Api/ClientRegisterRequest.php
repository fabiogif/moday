<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClientRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255'],
            'password' => ['required','string','min:6','confirmed'],
            'phone' => ['required','string','max:20'],
            'cpf' => ['nullable', 'string', 'max:14', new Cpf()],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}


