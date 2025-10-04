<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStoreRequest extends FormRequest
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
        $tenantId = auth()->user()->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('roles', 'name')->where('tenant_id', $tenantId)
            ],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[a-z0-9\-_]+$/',
                Rule::unique('roles', 'slug')->where('tenant_id', $tenantId)
            ],
            'description' => 'nullable|string|max:1000',
            'level' => 'required|integer|min:1|max:5',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do role é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'name.unique' => 'Este nome de role já está em uso.',
            
            'slug.required' => 'O slug do role é obrigatório.',
            'slug.min' => 'O slug deve ter pelo menos 3 caracteres.',
            'slug.max' => 'O slug não pode ter mais de 255 caracteres.',
            'slug.regex' => 'O slug deve conter apenas letras minúsculas, números, hífens e underscores.',
            'slug.unique' => 'Este slug já está em uso.',
            
            'description.max' => 'A descrição não pode ter mais de 1000 caracteres.',
            
            'level.required' => 'O nível do role é obrigatório.',
            'level.integer' => 'O nível deve ser um número inteiro.',
            'level.min' => 'O nível deve ser pelo menos 1.',
            'level.max' => 'O nível não pode ser maior que 5.',
            
            'is_active.boolean' => 'O status ativo deve ser verdadeiro ou falso.',
        ];
    }
}
