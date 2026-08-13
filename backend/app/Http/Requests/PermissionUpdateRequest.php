<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionUpdateRequest extends FormRequest
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
        $permissionId = $this->route('id');
        
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('permissions', 'slug')->ignore($permissionId)->where('tenant_id', auth()->user()->tenant_id)
            ],
            'description' => 'nullable|string|max:500',
            'module' => 'sometimes|string|max:100',
            'action' => 'sometimes|string|max:100',
            'resource' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
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
            'name.string' => 'O nome deve ser uma string.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'slug.unique' => 'Este slug já está em uso.',
            'slug.max' => 'O slug não pode ter mais de 255 caracteres.',
            'description.max' => 'A descrição não pode ter mais de 500 caracteres.',
            'module.string' => 'O módulo deve ser uma string.',
            'module.max' => 'O módulo não pode ter mais de 100 caracteres.',
            'action.string' => 'A ação deve ser uma string.',
            'action.max' => 'A ação não pode ter mais de 100 caracteres.',
            'resource.string' => 'O recurso deve ser uma string.',
            'resource.max' => 'O recurso não pode ter mais de 100 caracteres.',
            'is_active.boolean' => 'O status ativo deve ser verdadeiro ou falso.',
        ];
    }
}
