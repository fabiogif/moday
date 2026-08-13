<?php

namespace App\Http\Requests\Api;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MigratePlanRequest extends FormRequest
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
     * Self-service do tenant só pode migrar para plano gratuito (price <= 0).
     * Planos pagos exigem /api/subscription/payment. Admin usa outro endpoint.
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $planId = $this->input('plan_id');
            if (!$planId || $validator->errors()->has('plan_id')) {
                return;
            }

            $plan = Plan::query()->find($planId);
            if ($plan && !$plan->isFree()) {
                $validator->errors()->add(
                    'plan_id',
                    'Planos pagos exigem pagamento. Use a assinatura com cartão.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'O ID do plano é obrigatório.',
            'plan_id.integer' => 'O ID do plano deve ser um número inteiro.',
            'plan_id.exists' => 'O plano selecionado não existe ou está inativo.',
            'notes.max' => 'As notas não podem ter mais de 500 caracteres.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $validator->errors()
        ], 422));
    }
}
