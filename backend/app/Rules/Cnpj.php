<?php

namespace App\Rules;

use App\Support\BrazilianDocuments;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!BrazilianDocuments::isValidCnpj((string) $value)) {
            $fail('O CNPJ informado é inválido.');
        }
    }
}
