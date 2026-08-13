<?php

namespace App\Http\Requests\Api\Visit;

use App\Models\Visit;
use App\Models\VisitRecurrence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRecurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'frequency' => ['required', 'string', Rule::in(VisitRecurrence::FREQUENCIES)],
            'interval_count' => ['nullable', 'integer', 'min:1', 'max:52'],
            'days_of_week' => ['required_if:frequency,weekly', 'nullable', 'array'],
            'days_of_week.*' => ['integer', 'min:0', 'max:6'],
            'day_of_month' => ['required_if:frequency,monthly', 'nullable', 'integer', 'min:1', 'max:31'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'scheduled_end_time' => ['required', 'date_format:H:i', 'after:scheduled_start_time'],
            'type' => ['required', 'string', Rule::in(Visit::TYPES)],
            'priority' => ['nullable', 'string', Rule::in(Visit::PRIORITIES)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'O cliente é obrigatório.',
            'client_id.exists' => 'O cliente selecionado é inválido.',
            'frequency.required' => 'A frequência é obrigatória.',
            'frequency.in' => 'Frequência inválida.',
            'days_of_week.required_if' => 'Selecione ao menos um dia da semana para frequência semanal.',
            'day_of_month.required_if' => 'Informe o dia do mês para frequência mensal.',
            'scheduled_start_time.required' => 'O horário inicial é obrigatório.',
            'scheduled_end_time.after' => 'O horário final deve ser depois do horário inicial.',
            'type.required' => 'O tipo de visita é obrigatório.',
            'starts_on.required' => 'A data de início da recorrência é obrigatória.',
            'ends_on.after_or_equal' => 'A data de fim deve ser posterior ou igual à data de início.',
        ];
    }
}
