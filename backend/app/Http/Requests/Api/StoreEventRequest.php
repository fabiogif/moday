<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:promocao,aviso,outro'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_date' => ['required', 'date', 'after_or_equal:' . now()->subMinute()->format('Y-m-d H:i:s')],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'], // max 24h
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'client_ids' => ['required', 'array', 'min:1'],
            'client_ids.*' => ['required', 'integer', 'exists:clients,id'],
            'notification_channels' => ['nullable', 'array'],
            'notification_channels.*' => ['string', 'in:email,whatsapp,sms'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título do evento é obrigatório.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',
            'type.required' => 'O tipo do evento é obrigatório.',
            'type.in' => 'Selecione um tipo válido: Promoção, Aviso ou Outro.',
            'color.regex' => 'A cor deve estar no formato hexadecimal (#RRGGBB).',
            'start_date.required' => 'A data e hora de início são obrigatórias.',
            'start_date.date' => 'Informe uma data e hora válidas.',
            'start_date.after_or_equal' => 'A data e hora devem ser iguais ou posteriores ao momento atual.',
            'duration_minutes.required' => 'A duração do evento é obrigatória.',
            'duration_minutes.integer' => 'A duração deve ser um número inteiro.',
            'duration_minutes.min' => 'A duração mínima é de 1 minuto.',
            'duration_minutes.max' => 'A duração máxima é de 24 horas (1440 minutos).',
            'location.max' => 'O local não pode ter mais de 255 caracteres.',
            'description.required' => 'A descrição do evento é obrigatória.',
            'description.min' => 'A descrição deve ter pelo menos 10 caracteres.',
            'description.max' => 'A descrição não pode ter mais de 2000 caracteres.',
            'client_ids.required' => 'Selecione pelo menos um cliente para este evento.',
            'client_ids.array' => 'Os clientes devem ser informados em formato de lista.',
            'client_ids.min' => 'Selecione pelo menos um cliente.',
            'client_ids.*.integer' => 'Identificador de cliente inválido.',
            'client_ids.*.exists' => 'Um ou mais clientes selecionados não existem.',
            'notification_channels.array' => 'Os canais de notificação devem ser informados em formato de lista.',
            'notification_channels.*.in' => 'Canal de notificação inválido. Use: email, whatsapp ou sms.',
            'is_active.boolean' => 'O status do evento deve ser verdadeiro ou falso.',
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
            // Validar se clientes pertencem ao tenant do usuário
            $this->validateClientsOwnership($validator);
        });
    }

    private function validateClientsOwnership($validator)
    {
        $user = auth()->user();
        
        if (!$user || !$user->tenant_id) {
            return;
        }

        $clientIds = $this->input('client_ids', []);
        
        if (empty($clientIds)) {
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

