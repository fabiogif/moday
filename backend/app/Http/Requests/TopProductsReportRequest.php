<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class TopProductsReportRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel,csv',
            'limit' => 'nullable|integer|min:1|max:100',
            'category_id' => 'nullable|integer',
        ];
    }
    
    public function messages(): array
    {
        return [
            'start_date.required' => 'Data inicial é obrigatória.',
            'end_date.required' => 'Data final é obrigatória.',
            'end_date.after_or_equal' => 'Data final deve ser maior ou igual à data inicial.',
            'format.required' => 'Formato é obrigatório.',
            'format.in' => 'Formato deve ser: pdf, excel ou csv.',
            'limit.max' => 'Limite máximo de 100 produtos.',
        ];
    }
}

