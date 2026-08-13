<?php

namespace App\Http\Requests;

class PeriodReportPreviewRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'Data inicial é obrigatória.',
            'end_date.required' => 'Data final é obrigatória.',
            'end_date.after_or_equal' => 'Data final deve ser maior ou igual à data inicial.',
        ];
    }
}
