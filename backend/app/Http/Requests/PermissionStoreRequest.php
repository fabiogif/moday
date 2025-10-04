<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionStoreRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:permissions,slug,NULL,id,tenant_id,' . auth()->user()->tenant_id,
            'description' => 'nullable|string|max:500',
            'module' => 'required|string|max:100',
            'action' => 'required|string|max:100',
            'resource' => 'required|string|max:100',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'slug.unique' => 'Este slug já está em uso.',
            'slug.max' => 'O slug não pode ter mais de 255 caracteres.',
            'description.max' => 'A descrição não pode ter mais de 500 caracteres.',
            'module.required' => 'O módulo é obrigatório.',
            'module.max' => 'O módulo não pode ter mais de 100 caracteres.',
            'action.required' => 'A ação é obrigatória.',
            'action.max' => 'A ação não pode ter mais de 100 caracteres.',
            'resource.required' => 'O recurso é obrigatório.',
            'resource.max' => 'O recurso não pode ter mais de 100 caracteres.',
            'is_active.boolean' => 'O status ativo deve ser verdadeiro ou falso.',
        ];
    }
}
