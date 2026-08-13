<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
            'name' => 'required|unique:tenants|min:3|max:255',
            'cnpj' => 'required|unique:tenants|min:3|max:255',
            'email' => 'required|unique:tenants|min:3|max:255',
            'password' => 'required|min:3|max:255',
            // O cadastro público só pode selecionar planos gratuitos/trial (price <= 0).
            // Planos pagos só são ativados pelo fluxo de assinatura com pagamento confirmado
            // (ver SubscriptionDomainService / webhook do Mercado Pago), nunca no registro em si.
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(fn ($query) => $query->where('is_active', true)),
                function ($attribute, $value, $fail) {
                    $plan = Plan::find($value);
                    if ($plan && !$plan->isFree()) {
                        $fail('O plano selecionado não está disponível para cadastro direto. Faça upgrade após a criação da conta.');
                    }
                },
            ],
        ];
    }
}
