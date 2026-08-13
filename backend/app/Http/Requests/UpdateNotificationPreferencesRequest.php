<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autenticação já é garantida pelo middleware auth:api
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'preferences' => 'required|array',
            'preferences.*.event_type' => 'required|string',
            'preferences.*.email_enabled' => 'sometimes|boolean',
            'preferences.*.push_enabled' => 'sometimes|boolean',
            'preferences.*.sms_enabled' => 'sometimes|boolean',
            'preferences.*.frequency' => 'sometimes|string|in:instant,hourly,daily,weekly,never',
            'preferences.*.quiet_hours_start' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'preferences.*.quiet_hours_end' => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'As preferências são obrigatórias',
            'preferences.array' => 'As preferências devem ser um array',
            'preferences.*.event_type.required' => 'O tipo de evento é obrigatório',
            'preferences.*.frequency.in' => 'A frequência deve ser: instant, hourly, daily, weekly ou never',
            'preferences.*.quiet_hours_start.regex' => 'Formato de hora inválido (use HH:MM, exemplo: 22:00)',
            'preferences.*.quiet_hours_end.regex' => 'Formato de hora inválido (use HH:MM, exemplo: 06:00)',
        ];
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converter valores booleanos de string para bool se necessário
        if ($this->has('preferences') && is_array($this->preferences)) {
            $preferences = $this->preferences;
            
            foreach ($preferences as $key => $preference) {
                if (!is_array($preference)) {
                    continue;
                }
                
                // Converter booleanos
                if (isset($preference['email_enabled']) && is_string($preference['email_enabled'])) {
                    $preferences[$key]['email_enabled'] = filter_var($preference['email_enabled'], FILTER_VALIDATE_BOOLEAN);
                }
                if (isset($preference['push_enabled']) && is_string($preference['push_enabled'])) {
                    $preferences[$key]['push_enabled'] = filter_var($preference['push_enabled'], FILTER_VALIDATE_BOOLEAN);
                }
                if (isset($preference['sms_enabled']) && is_string($preference['sms_enabled'])) {
                    $preferences[$key]['sms_enabled'] = filter_var($preference['sms_enabled'], FILTER_VALIDATE_BOOLEAN);
                }
                
                // Remover quiet_hours se for null ou string vazia
                if (isset($preference['quiet_hours_start']) && ($preference['quiet_hours_start'] === null || $preference['quiet_hours_start'] === '')) {
                    unset($preferences[$key]['quiet_hours_start']);
                }
                if (isset($preference['quiet_hours_end']) && ($preference['quiet_hours_end'] === null || $preference['quiet_hours_end'] === '')) {
                    unset($preferences[$key]['quiet_hours_end']);
                }
            }
            
            $this->merge(['preferences' => $preferences]);
        }
    }
}

