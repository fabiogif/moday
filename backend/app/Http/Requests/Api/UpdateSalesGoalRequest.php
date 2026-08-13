<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSalesGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'goal_type' => ['sometimes', 'string', 'in:seller,team,region,product'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'target_profile_id' => ['nullable', 'integer', 'exists:profiles,id'],
            'target_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'period_type' => ['sometimes', 'string', 'in:monthly,quarterly,annual'],
            'period_start' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'period_end' => ['sometimes', 'date', 'date_format:Y-m-d', 'after:period_start'],
            'target_value' => ['sometimes', 'numeric', 'min:0.01'],
            'status' => ['sometimes', 'string', 'in:active,completed,expired,cancelled'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'goal_type.in' => 'Tipo de meta deve ser: vendedor, equipe, região ou produto.',
            'period_type.in' => 'Período deve ser: mensal, trimestral ou anual.',
            'period_end.after' => 'A data final deve ser posterior à data inicial.',
            'target_value.min' => 'O valor alvo deve ser maior que zero.',
            'status.in' => 'Status deve ser: active, completed, expired ou cancelled.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}
