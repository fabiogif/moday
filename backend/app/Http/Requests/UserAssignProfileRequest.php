<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAssignProfileRequest extends FormRequest
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
            'profile_id' => 'required|exists:profiles,id',
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
            'profile_id.required' => 'O ID do perfil é obrigatório.',
            'profile_id.exists' => 'O perfil selecionado não existe.',
        ];
    }
}
