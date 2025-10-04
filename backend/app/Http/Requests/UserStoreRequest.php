<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email,NULL,id,tenant_id,' . auth()->user()->tenant_id,
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'profiles' => 'nullable|array',
            'profiles.*' => 'exists:profiles,id',
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
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ter um formato válido.',
            'email.unique' => 'Este email já está em uso.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'avatar.max' => 'O avatar não pode ter mais de 255 caracteres.',
            'profiles.array' => 'Os perfis devem ser um array.',
            'profiles.*.exists' => 'Um ou mais perfis selecionados são inválidos.',
        ];
    }
}
