<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SalesPerformanceRequest extends BaseRequest
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
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
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
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'start_date.before_or_equal' => 'A data de início deve ser anterior ou igual à data de fim.',
            'end_date.date' => 'A data de fim deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de fim deve ser posterior ou igual à data de início.',
            'days.integer' => 'O número de dias deve ser um número inteiro.',
            'days.min' => 'O número de dias deve ser no mínimo 1.',
            'days.max' => 'O número de dias deve ser no máximo 365.',
        ];
    }
}

