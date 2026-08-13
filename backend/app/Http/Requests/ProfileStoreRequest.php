<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileStoreRequest extends FormRequest
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
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id',
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
            'description.max' => 'A descrição não pode ter mais de 500 caracteres.',
            'is_active.boolean' => 'O status ativo deve ser verdadeiro ou falso.',
            'permissions.required' => 'Informe ao menos uma permissão para o perfil.',
            'permissions.array' => 'As permissões devem ser enviadas em formato de lista.',
            'permissions.min' => 'Selecione ao menos uma permissão.',
            'permissions.*.integer' => 'Cada permissão deve ser um identificador válido.',
            'permissions.*.exists' => 'Uma ou mais permissões selecionadas são inválidas.',
        ];
    }
}
