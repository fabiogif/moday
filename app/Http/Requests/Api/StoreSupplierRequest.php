<?php

namespace App\Http\Requests\Api;

use App\Classes\ApiResponseClass;
use App\Rules\Cnpj;
use App\Support\BrazilianDocuments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // B2B-primary fields (company_name required OR name required)
            'company_name'      => ['nullable', 'string', 'max:255'],
            'trade_name'        => ['nullable', 'string', 'max:255'],
            'cnpj'              => ['nullable', 'string', 'max:18', new Cnpj()],
            'state_registration'=> ['nullable', 'string', 'max:50'],
            // Legacy field names (accepted for backward compat)
            'name'              => ['nullable', 'string', 'max:255'],
            'fantasy_name'      => ['nullable', 'string', 'max:255'],
            'document'          => ['nullable', 'string', 'max:18'],
            'document_type'     => ['nullable', 'in:cpf,cnpj'],
            // Contact
            'email'             => ['nullable', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:20'],
            'phone2'            => ['nullable', 'string', 'max:20'],
            'whatsapp'          => ['nullable', 'string', 'max:20'],
            'contact_name'      => ['nullable', 'string', 'max:255'],
            'contact_phone'     => ['nullable', 'string', 'max:20'],
            // B2B commercial
            'payment_term_days' => ['nullable', 'integer', 'min:0'],
            'lead_time_days'    => ['nullable', 'integer', 'min:0'],
            'min_order_value'   => ['nullable', 'numeric', 'min:0'],
            // Address
            'address'           => ['nullable', 'string', 'max:255'],
            'number'            => ['nullable', 'string', 'max:20'],
            'complement'        => ['nullable', 'string', 'max:100'],
            'neighborhood'      => ['nullable', 'string', 'max:100'],
            'city'              => ['nullable', 'string', 'max:100'],
            'state'             => ['nullable', 'string', 'size:2'],
            'zip_code'          => ['nullable', 'string', 'max:9'],
            // Banking
            'bank_name'         => ['nullable', 'string', 'max:100'],
            'bank_agency'       => ['nullable', 'string', 'max:20'],
            'bank_account'      => ['nullable', 'string', 'max:20'],
            'pix_key'           => ['nullable', 'string', 'max:255'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'is_active'         => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $data = $v->getData();
            $hasName = !empty($data['company_name']) || !empty($data['name']);
            if (!$hasName) {
                $v->errors()->add('company_name', 'A razão social do fornecedor é obrigatória.');
            }

            if (!empty($data['document']) && !empty($data['document_type'])) {
                $valid = $data['document_type'] === 'cpf'
                    ? BrazilianDocuments::isValidCpf($data['document'])
                    : BrazilianDocuments::isValidCnpj($data['document']);

                if (!$valid) {
                    $label = $data['document_type'] === 'cpf' ? 'CPF' : 'CNPJ';
                    $v->errors()->add('document', "O {$label} informado é inválido.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.email'  => 'Informe um e-mail válido.',
            'state.size'   => 'O estado deve ter 2 caracteres (UF).',
            'document_type.in' => 'O tipo de documento deve ser cpf ou cnpj.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponseClass::validationError($validator->errors(), 'Dados inválidos')
        );
    }
}
