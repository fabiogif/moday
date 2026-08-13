<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest as BaseRequest;

class StoreBankAccountRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'account_type' => ['required', 'in:checking,savings,payment'],
            'bank_code' => ['required', 'string', 'max:10'],
            'agency' => ['required', 'string', 'max:20'],
            'agency_digit' => ['nullable', 'string', 'max:2'],
            'account_number' => ['required', 'string', 'max:20'],
            'account_digit' => ['required', 'string', 'max:2'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_holder_document' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $length = strlen($value);
                    if (!in_array($length, [11, 14], true)) {
                        $fail('O CPF/CNPJ deve ter 11 ou 14 dígitos.');
                    }
                },
            ],
            'account_holder_type' => ['required', 'in:individual,company'],
            'pix_key_type' => ['nullable', 'in:cpf,cnpj,email,phone,random'],
            'pix_key' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
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

        if ($this->has('pix_key_type') && $this->pix_key_type === null) {
            $this->merge(['pix_key' => null]);
        }
    }
}

