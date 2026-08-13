<?php

namespace App\Http\Requests\Api\Visit;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeVisitStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Visit::STATUSES)],
            'reason' => ['required_if:status,cancelada,cliente_ausente', 'nullable', 'string', 'max:1000'],
            'reschedule_to' => ['required_if:status,reagendada', 'nullable', 'array'],
            'reschedule_to.scheduled_date' => ['required_if:status,reagendada', 'nullable', 'date'],
            'reschedule_to.scheduled_start_time' => ['required_if:status,reagendada', 'nullable', 'date_format:H:i'],
            'reschedule_to.scheduled_end_time' => ['required_if:status,reagendada', 'nullable', 'date_format:H:i', 'after:reschedule_to.scheduled_start_time'],
            'reschedule_to.user_id' => ['nullable', 'integer'],
            'reschedule_to.force' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'A situação da visita é obrigatória.',
            'status.in' => 'Situação inválida.',
            'reason.required_if' => 'O motivo é obrigatório para cancelamento ou cliente ausente.',
            'reschedule_to.required_if' => 'Os dados de reagendamento (nova data e horário) são obrigatórios.',
        ];
    }
}
