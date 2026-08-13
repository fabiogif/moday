<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreStoreHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'is_always_open' => ['nullable', 'boolean'],
            'delivery_type' => ['required', 'in:delivery,pickup,both'],
            'is_active' => ['nullable', 'boolean'],
        ];

        // Se não é "sempre aberto", os campos de horário são obrigatórios
        if (!$this->input('is_always_open')) {
            $rules['day_of_week'] = ['required', 'integer', 'between:0,6'];
            $rules['start_time'] = ['required', 'date_format:H:i'];
            $rules['end_time'] = ['required', 'date_format:H:i', 'after:start_time'];
            
            // Segundo período (opcional, para intervalos)
            $rules['start_time_2'] = ['nullable', 'date_format:H:i', 'after:end_time'];
            $rules['end_time_2'] = ['nullable', 'date_format:H:i', 'after:start_time_2'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'day_of_week.required' => 'O dia da semana é obrigatório.',
            'day_of_week.between' => 'Dia da semana inválido. Use 0 (Domingo) a 6 (Sábado).',
            'delivery_type.required' => 'O tipo de serviço é obrigatório.',
            'delivery_type.in' => 'Tipo de serviço inválido. Use: delivery, pickup ou both.',
            'start_time.required' => 'O horário de início é obrigatório.',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'end_time.required' => 'O horário de término é obrigatório.',
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

