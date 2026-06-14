<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class MonthlyFinancialReportRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2099',
            'format' => 'required|in:pdf,excel,csv',
        ];
    }
    
    public function messages(): array
    {
        return [
            'month.required' => 'Mês é obrigatório.',
            'month.min' => 'Mês deve ser entre 1 e 12.',
            'month.max' => 'Mês deve ser entre 1 e 12.',
            'year.required' => 'Ano é obrigatório.',
            'year.min' => 'Ano deve ser entre 2020 e 2099.',
            'year.max' => 'Ano deve ser entre 2020 e 2099.',
            'format.required' => 'Formato é obrigatório.',
            'format.in' => 'Formato deve ser: pdf, excel ou csv.',
        ];
    }
}

