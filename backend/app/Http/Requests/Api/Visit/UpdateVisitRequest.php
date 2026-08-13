<?php

namespace App\Http\Requests\Api\Visit;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitRequest extends FormRequest
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
                'sometimes',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'user_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'scheduled_date' => ['sometimes', 'date'],
            'scheduled_start_time' => ['sometimes', 'date_format:H:i'],
            'scheduled_end_time' => ['sometimes', 'date_format:H:i', 'after:scheduled_start_time'],
            'type' => ['sometimes', 'string', Rule::in(Visit::TYPES)],
            'priority' => ['sometimes', 'string', Rule::in(Visit::PRIORITIES)],
            'objective_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'force' => ['sometimes', 'boolean'],
        ];
    }
}
