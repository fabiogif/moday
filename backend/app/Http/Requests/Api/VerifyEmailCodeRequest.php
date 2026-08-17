<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código de verificação.',
            'code.size' => 'O código deve ter 6 dígitos.',
            'code.regex' => 'O código deve conter apenas números.',
        ];
    }
}
