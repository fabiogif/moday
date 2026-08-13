<?php

namespace App\Http\Requests\Api\Visit;

use App\Models\Visit;
use App\Models\VisitRecurrence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitRecurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'user_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'frequency' => ['sometimes', 'string', Rule::in(VisitRecurrence::FREQUENCIES)],
            'interval_count' => ['sometimes', 'integer', 'min:1', 'max:52'],
            'days_of_week' => ['sometimes', 'nullable', 'array'],
            'days_of_week.*' => ['integer', 'min:0', 'max:6'],
            'day_of_month' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'scheduled_start_time' => ['sometimes', 'date_format:H:i'],
            'scheduled_end_time' => ['sometimes', 'date_format:H:i', 'after:scheduled_start_time'],
            'type' => ['sometimes', 'string', Rule::in(Visit::TYPES)],
            'priority' => ['sometimes', 'string', Rule::in(Visit::PRIORITIES)],
            'ends_on' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'frequency.in' => 'Frequência inválida.',
            'scheduled_end_time.after' => 'O horário final deve ser depois do horário inicial.',
            'type.in' => 'Tipo de visita inválido.',
            'priority.in' => 'Prioridade inválida.',
        ];
    }
}
