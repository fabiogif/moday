<?php

namespace App\Rules;

use App\Models\Batch;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidBatchForSale implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return; // batch_id is nullable — no batch means no validation needed
        }

        $batch = Batch::find($value);

        if (!$batch) {
            $fail('O lote informado não foi encontrado.');
            return;
        }

        if ($batch->status === 'expired') {
            $fail("O lote {$batch->batch_number} está vencido e não pode ser vendido.");
            return;
        }

        if ($batch->status === 'quarantine') {
            $fail("O lote {$batch->batch_number} está em quarentena e não pode ser vendido.");
            return;
        }

        if ($batch->status === 'recalled') {
            $fail("O lote {$batch->batch_number} foi objeto de recall e não pode ser vendido.");
            return;
        }

        if ($batch->expiry_date && $batch->expiry_date->isPast()) {
            $fail("O lote {$batch->batch_number} venceu em {$batch->expiry_date->format('d/m/Y')} e não pode ser vendido.");
            return;
        }
    }
}
