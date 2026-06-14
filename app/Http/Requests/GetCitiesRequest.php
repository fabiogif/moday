<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCitiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:10', 'max:500'],
            'search' => ['sometimes', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'O parâmetro per_page deve ser um número inteiro',
            'per_page.min' => 'O mínimo é 10 itens por página',
            'per_page.max' => 'O máximo é 500 itens por página',
            'search.max' => 'O termo de busca não pode ter mais de 100 caracteres',
        ];
    }
}

