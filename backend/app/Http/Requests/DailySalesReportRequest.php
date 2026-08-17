<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class DailySalesReportRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel,csv',
            'client_id' => 'nullable|integer',
            'status' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ];
    }
    
    public function messages(): array
    {
        return [
            'date.date' => 'Data inválida.',
            'start_date.date' => 'Data inicial inválida.',
            'end_date.date' => 'Data final inválida.',
            'end_date.after_or_equal' => 'Data final deve ser maior ou igual à data inicial.',
            'format.required' => 'Formato é obrigatório.',
            'format.in' => 'Formato deve ser: pdf, excel ou csv.',
        ];
    }
}

