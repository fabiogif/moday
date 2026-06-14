<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachPermissionToRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_id' => 'required|exists:permissions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'permission_id.required' => 'A permissão é obrigatória.',
            'permission_id.exists' => 'A permissão selecionada é inválida.',
        ];
    }
}


