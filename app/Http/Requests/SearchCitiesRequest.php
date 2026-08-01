<?php

namespace App\Http\Requests;

class SearchCitiesRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'O parâmetro de pesquisa é obrigatório',
            'q.min' => 'Digite pelo menos 2 caracteres para pesquisar',
            'q.max' => 'O termo de pesquisa não pode ter mais de 100 caracteres',
        ];
    }
}
