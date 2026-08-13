<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $serviceTypeId = $this->route('id');
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types', 'name')->ignore($serviceTypeId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('service_types', 'slug')->ignore($serviceTypeId),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'requires_address' => 'nullable|boolean',
            'requires_table' => 'nullable|boolean',
            'available_in_menu' => 'nullable|boolean',
            'order_position' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tipo de atendimento é obrigatório.',
            'name.unique' => 'Já existe um tipo de atendimento com este nome.',
            'slug.unique' => 'Já existe um tipo de atendimento com este slug.',
        ];
    }
}













