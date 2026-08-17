<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class ClientsReportRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'format' => 'required|in:pdf,excel,csv',
            'is_active' => 'nullable|boolean',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
    
    public function messages(): array
    {
        return [
            'format.required' => 'Formato é obrigatório.',
            'format.in' => 'Formato deve ser: pdf, excel ou csv.',
            'state.max' => 'Estado deve ter no máximo 2 caracteres.',
        ];
    }
}

