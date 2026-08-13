<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RedeemRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reward_id' => ['required', 'integer', 'exists:loyalty_rewards,id'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'reward_id.required' => 'Selecione uma recompensa.',
            'reward_id.exists' => 'Recompensa não encontrada.',
            'client_id.required' => 'Cliente é obrigatório.',
            'client_id.exists' => 'Cliente não encontrado.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}

