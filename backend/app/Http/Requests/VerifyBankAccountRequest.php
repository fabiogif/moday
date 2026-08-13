<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest as BaseRequest;

class VerifyBankAccountRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'verification_method' => ['required', 'string', 'in:manual,api,document'],
        ];
    }
}

