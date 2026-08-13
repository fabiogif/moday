<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseRequest;

class StorePDVFeedbackRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Usuário autenticado pode enviar feedback
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:suggestion,bug,praise,other',
            'rating' => 'nullable|integer|min:1|max:5',
            'message' => 'nullable|string|max:2000',
            'quick_emoji' => 'nullable|string|in:😊,😐,😞',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'type.required' => 'O tipo de feedback é obrigatório.',
            'type.in' => 'O tipo de feedback deve ser: suggestion, bug, praise ou other.',
            'rating.integer' => 'A avaliação deve ser um número inteiro.',
            'rating.min' => 'A avaliação deve ser no mínimo 1 estrela.',
            'rating.max' => 'A avaliação deve ser no máximo 5 estrelas.',
            'message.max' => 'A mensagem não pode ter mais de 2000 caracteres.',
            'quick_emoji.in' => 'O emoji deve ser um dos seguintes: 😊, 😐, 😞.',
        ];
    }
}

