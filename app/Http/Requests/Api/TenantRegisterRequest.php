<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TenantRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('company_cnpj')) {
            $this->merge([
                'company_cnpj' => preg_replace('/\D/', '', (string) $this->input('company_cnpj')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // Dados da empresa
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_cnpj' => 'required|string|size:14|regex:/^\d{14}$/|not_regex:/^(\d)\1{13}$/|unique:tenants,cnpj',

            // Dados do usuário administrador
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',

            // Plano
            'plan_id' => 'required|exists:plans,id',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'O nome da empresa é obrigatório.',
            'name.required' => 'O nome do usuário é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser válido.',
            'email.unique' => 'Este email já está sendo usado.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'plan_id.required' => 'É necessário selecionar um plano.',
            'plan_id.exists' => 'Plano selecionado é inválido.',
            'company_cnpj.required' => 'O CNPJ da empresa é obrigatório.',
            'company_cnpj.size' => 'O CNPJ deve ter 14 dígitos.',
            'company_cnpj.regex' => 'O CNPJ deve conter apenas números.',
            'company_cnpj.not_regex' => 'CNPJ inválido.',
            'company_cnpj.unique' => 'Este CNPJ já está cadastrado.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}


