<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseRequest;

class UpdatePDVFeedbackStatusRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Usuário autenticado pode atualizar status (admin)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|string|in:pending,reviewed,resolved,dismissed',
            'review_notes' => 'nullable|string|max:1000',
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
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser: pending, reviewed, resolved ou dismissed.',
            'review_notes.max' => 'As notas de revisão não podem ter mais de 1000 caracteres.',
        ];
    }
}

