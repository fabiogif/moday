<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSalesGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'goal_type' => ['required', 'string', 'in:seller,team,region,product'],
            'target_user_id' => ['required_if:goal_type,seller', 'nullable', 'integer', 'exists:users,id'],
            'target_profile_id' => ['required_if:goal_type,team,region', 'nullable', 'integer', 'exists:profiles,id'],
            'target_product_id' => ['required_if:goal_type,product', 'nullable', 'integer', 'exists:products,id'],
            'period_type' => ['required', 'string', 'in:monthly,quarterly,annual'],
            'period_start' => ['required', 'date', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date', 'date_format:Y-m-d', 'after:period_start'],
            'target_value' => ['required', 'numeric', 'min:0.01'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título da meta é obrigatório.',
            'goal_type.required' => 'O tipo de meta é obrigatório.',
            'goal_type.in' => 'Tipo de meta deve ser: vendedor, equipe, região ou produto.',
            'target_user_id.required_if' => 'Selecione o vendedor para metas individuais.',
            'target_profile_id.required_if' => 'Selecione o perfil/equipe para metas de equipe ou região.',
            'target_product_id.required_if' => 'Selecione o produto para metas de produto.',
            'period_type.required' => 'O período da meta é obrigatório.',
            'period_type.in' => 'Período deve ser: mensal, trimestral ou anual.',
            'period_start.required' => 'A data de início é obrigatória.',
            'period_end.required' => 'A data de término é obrigatória.',
            'period_end.after' => 'A data final deve ser posterior à data inicial.',
            'target_value.required' => 'O valor alvo é obrigatório.',
            'target_value.min' => 'O valor alvo deve ser maior que zero.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}
