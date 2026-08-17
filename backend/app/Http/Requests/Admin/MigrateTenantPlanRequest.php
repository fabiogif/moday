<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MigrateTenantPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'integer',
                Rule::exists('tenants', 'id'),
            ],
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'reason' => 'nullable|string|max:500',
            'notify_tenant' => 'nullable|boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.required' => 'O ID da empresa é obrigatório.',
            'tenant_id.integer' => 'O ID da empresa deve ser um número inteiro.',
            'tenant_id.exists' => 'A empresa selecionada não existe.',
            'plan_id.required' => 'O ID do plano é obrigatório.',
            'plan_id.integer' => 'O ID do plano deve ser um número inteiro.',
            'plan_id.exists' => 'O plano selecionado não existe ou não está ativo.',
            'reason.string' => 'O motivo deve ser uma string.',
            'reason.max' => 'O motivo não pode ter mais de :max caracteres.',
            'notify_tenant.boolean' => 'O campo notificar empresa deve ser verdadeiro ou falso.',
        ];
    }
}

