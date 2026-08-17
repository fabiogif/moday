<?php

namespace App\Http\Requests;

use App\Rules\Cnpj;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;

class StoreClient extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $tenantId  = $this->user()?->tenant_id;
        $requestId = $this->input('client_request_id');

        // Se este client_request_id já foi usado, excluímos o próprio registro das
        // checagens de unicidade abaixo - um retry legítimo reenvia os mesmos dados
        // (mesmo email/cpf) e não deve ser rejeitado como duplicata de si mesmo.
        $excludeId = 'NULL';
        if ($requestId && $tenantId) {
            $existingId = \App\Models\Client::where('tenant_id', $tenantId)
                ->where('client_request_id', $requestId)
                ->value('id');
            if ($existingId) {
                $excludeId = $existingId;
            }
        }

        return [
            // name required only when company_name not provided (B2B uses company_name)
            'name'         => 'nullable|string|min:3|max:255',
            'company_name' => 'nullable|string|max:255',
            'cpf'          => ['nullable', 'string', 'min:11', 'max:14', new Cpf(), 'unique:clients,cpf,' . $excludeId . ',id,tenant_id,' . $tenantId],
            'email'        => 'nullable|email|min:3|max:255|unique:clients,email,' . $excludeId . ',id,tenant_id,' . $tenantId,
            'phone'        => 'nullable|string|min:10|max:20',

            // Chave de idempotência gerada pelo cliente (app offline) - sem regra unique
            // de propósito: um retry legítimo deve devolver o registro existente, não um 422.
            'client_request_id' => 'nullable|string|max:64',

            // Senha opcional para clientes (pode ser gerada automaticamente)
            'password' => 'nullable|string|min:6|max:60',
            
            // Campos de endereço opcionais
            'address' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip_code' => 'nullable|string|max:20',

            // Campos B2B distribuidor
            'cnpj' => ['nullable', 'string', 'max:18', new Cnpj()],
            'company_name' => 'nullable|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'state_registration' => 'nullable|string|max:50',
            'client_type' => 'nullable|string|in:farmacia,hospital,clinica,mercado,atacado,outro',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_term_days' => 'nullable|integer|min:0',
            'price_table_id' => 'nullable|integer',
            'is_blocked' => 'sometimes|boolean',
            'blocked_reason' => 'nullable|string|max:500',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
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
            'name.required' => 'O nome completo é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.min' => 'O CPF deve ter pelo menos 11 caracteres.',
            'cpf.max' => 'O CPF não pode ter mais de 14 caracteres.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ter um formato válido.',
            'email.unique' => 'Este email já está cadastrado.',
            
            'phone.required' => 'O telefone é obrigatório.',
            'phone.min' => 'O telefone deve ter pelo menos 10 caracteres.',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            
            'address.max' => 'O logradouro não pode ter mais de 255 caracteres.',
            'number.max' => 'O número não pode ter mais de 20 caracteres.',
            'complement.max' => 'O complemento não pode ter mais de 100 caracteres.',
            'neighborhood.max' => 'O bairro não pode ter mais de 100 caracteres.',
            'city.max' => 'A cidade não pode ter mais de 100 caracteres.',
            'state.max' => 'O estado não pode ter mais de 50 caracteres.',
            'zip_code.max' => 'O CEP não pode ter mais de 20 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome completo',
            'cpf' => 'CPF',
            'email' => 'email',
            'phone' => 'telefone',
            'password' => 'senha',
            'address' => 'logradouro',
            'number' => 'número',
            'complement' => 'complemento',
            'neighborhood' => 'bairro',
            'city' => 'cidade',
            'state' => 'estado',
            'zip_code' => 'CEP',
        ];
    }
}
