<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'permission_ids.required' => 'A lista de permissões é obrigatória.',
            'permission_ids.array' => 'A lista de permissões deve ser um array.',
            'permission_ids.*.exists' => 'Uma ou mais permissões selecionadas são inválidas.',
        ];
    }
}


