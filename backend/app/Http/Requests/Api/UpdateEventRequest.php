<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:promocao,aviso,outro'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            // Validação melhorada: data deve ser válida e não pode ser muito antiga
            'start_date' => [
                'sometimes',
                'date',
                'after_or_equal:' . now()->subMinute()->format('Y-m-d H:i:s')
            ],
            'duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:10', 'max:2000'],
            'client_ids' => ['sometimes', 'array'],
            'client_ids.*' => ['integer', 'exists:clients,id'],
            'notification_channels' => ['nullable', 'array'],
            'notification_channels.*' => ['string', 'in:email,whatsapp,sms'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'O título deve ser um texto válido.',
            'type.in' => 'O tipo deve ser: promoção, aviso ou outro.',
            'start_date.date' => 'A data de início deve ser válida.',
            'duration_minutes.min' => 'A duração mínima é 1 minuto.',
            'duration_minutes.max' => 'A duração máxima é 24 horas (1440 minutos).',
            'description.min' => 'A descrição deve ter pelo menos 10 caracteres.',
            'notification_channels.*.in' => 'Canais válidos: email, whatsapp, sms.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateClientsOwnership($validator);
        });
    }

    private function validateClientsOwnership($validator)
    {
        $user = auth()->user();
        
        if (!$user || !$user->tenant_id) {
            return;
        }

        $clientIds = $this->input('client_ids');
        
        if (!$clientIds || empty($clientIds)) {
            return;
        }

        $validClientIds = \App\Models\Client::whereIn('id', $clientIds)
            ->where('tenant_id', $user->tenant_id)
            ->pluck('id')
            ->toArray();

        $invalidIds = array_diff($clientIds, $validClientIds);

        if (!empty($invalidIds)) {
            $validator->errors()->add(
                'client_ids',
                'Um ou mais clientes selecionados não pertencem a este estabelecimento.'
            );
        }
    }
}

