<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'order_id' => 'required_without:order_identify|nullable|exists:orders,id',
            'order_identify' => 'required_without:order_id|nullable|exists:orders,identify',
            'user_id' => 'nullable|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'customer_name' => 'required_without:user_id|string|max:255',
            'customer_email' => 'nullable|email|max:255',
        ];
    }
    
    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Se order_identify foi enviado ao invés de order_id, converter
        if ($this->has('order_identify') && !$this->has('order_id')) {
            $order = \App\Models\Order::where('identify', $this->order_identify)->first();
            if ($order) {
                $this->merge([
                    'order_id' => $order->id,
                ]);
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant é obrigatório.',
            'tenant_id.exists' => 'Tenant não encontrado.',
            'order_id.required' => 'Pedido é obrigatório.',
            'order_id.exists' => 'Pedido não encontrado.',
            'rating.required' => 'Avaliação é obrigatória.',
            'rating.min' => 'Avaliação deve ser no mínimo 1 estrela.',
            'rating.max' => 'Avaliação deve ser no máximo 5 estrelas.',
            'comment.max' => 'Comentário deve ter no máximo 1000 caracteres.',
            'customer_name.required_without' => 'Nome do cliente é obrigatório.',
            'customer_email.email' => 'E-mail inválido.',
        ];
    }
}

