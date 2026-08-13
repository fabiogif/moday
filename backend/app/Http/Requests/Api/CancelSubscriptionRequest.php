<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'nullable',
                'string',
                Rule::in([
                    'too_expensive',
                    'missing_features',
                    'competitor',
                    'not_needed',
                    'temporary',
                    'other',
                ]),
            ],
            'reason_detail' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.in' => 'Motivo de cancelamento inválido.',
            'reason_detail.max' => 'O detalhe do motivo pode ter no máximo 500 caracteres.',
        ];
    }
}
