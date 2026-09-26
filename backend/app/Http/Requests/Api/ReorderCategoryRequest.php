<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseRequest;

class ReorderCategoryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => 'required|array',
            'order.*' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'order.required' => 'A nova ordem das categorias é obrigatória.',
            'order.array' => 'A nova ordem das categorias deve ser um array.',
            'order.*.required' => 'Cada identificador de categoria deve ser preenchido.',
            'order.*.string' => 'Cada identificador de categoria deve ser uma string (UUID).',
        ];
    }
}
