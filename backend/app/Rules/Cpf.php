<?php

namespace App\Rules;

use App\Support\BrazilianDocuments;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!BrazilianDocuments::isValidCpf((string) $value)) {
            $fail('O CPF informado é inválido.');
        }
    }
}
