<?php

namespace App\Http\Requests\Api\Visit;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
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
            'scheduled_date' => ['required', 'date'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'scheduled_end_time' => ['required', 'date_format:H:i', 'after:scheduled_start_time'],
            'type' => ['required', 'string', Rule::in(Visit::TYPES)],
            'priority' => ['nullable', 'string', Rule::in(Visit::PRIORITIES)],
            'objective_notes' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'client_request_id' => ['nullable', 'string', 'max:64'],
            'force' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'O cliente é obrigatório.',
            'client_id.exists' => 'O cliente selecionado é inválido.',
            'user_id.exists' => 'O vendedor selecionado é inválido.',
            'scheduled_date.required' => 'A data da visita é obrigatória.',
            'scheduled_start_time.required' => 'O horário inicial é obrigatório.',
            'scheduled_end_time.required' => 'O horário final é obrigatório.',
            'scheduled_end_time.after' => 'O horário final deve ser depois do horário inicial.',
            'type.required' => 'O tipo de visita é obrigatório.',
            'type.in' => 'Tipo de visita inválido.',
            'priority.in' => 'Prioridade inválida.',
        ];
    }
}
