<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest as BaseRequest;

class UpdateBankAccountRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'account_type' => ['sometimes', 'in:checking,savings,payment'],
            'agency' => ['sometimes', 'string', 'max:20'],
            'agency_digit' => ['nullable', 'string', 'max:2'],
            'pix_key_type' => ['nullable', 'in:cpf,cnpj,email,phone,random'],
            'pix_key' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('account_holder_document')) {
            $this->merge([
                'account_holder_document' => preg_replace('/\D/', '', $this->account_holder_document),
            ]);
        }
    }
}

