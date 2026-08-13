<?php

namespace App\Http\Requests\Api\Visit;

use Illuminate\Foundation\Http\FormRequest;

class VisitReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'user_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.before_or_equal' => 'A data de início deve ser anterior ou igual à data de fim.',
            'date_to.after_or_equal' => 'A data de fim deve ser posterior ou igual à data de início.',
        ];
    }
}
