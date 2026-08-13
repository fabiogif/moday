<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateStoreHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_always_open' => ['nullable', 'boolean'],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'delivery_type' => ['nullable', 'in:delivery,pickup,both'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'start_time_2' => ['nullable', 'date_format:H:i', 'after:end_time'],
            'end_time_2' => ['nullable', 'date_format:H:i', 'after:start_time_2'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.between' => 'Dia da semana inválido. Use 0 (Domingo) a 6 (Sábado).',
            'delivery_type.in' => 'Tipo de serviço inválido. Use: delivery, pickup ou both.',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'end_time.date_format' => 'O horário de término deve estar no formato HH:MM.',
            'end_time.after' => 'O horário de término deve ser posterior ao horário de início.',
            'start_time_2.date_format' => 'O horário de início do 2º período deve estar no formato HH:MM.',
            'start_time_2.after' => 'O 2º período deve começar após o término do 1º período.',
            'end_time_2.date_format' => 'O horário de término do 2º período deve estar no formato HH:MM.',
            'end_time_2.after' => 'O horário de término do 2º período deve ser posterior ao seu início.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}

