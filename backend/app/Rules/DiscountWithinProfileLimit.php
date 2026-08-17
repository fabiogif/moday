<?php

namespace App\Rules;

use App\Models\Profile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DiscountWithinProfileLimit implements ValidationRule
{
    public function __construct(private readonly ?float $maxAllowed) {}

    public static function forUser(?\App\Models\User $user): self
    {
        if (!$user?->profile_id) {
            return new self(null); // no profile → no limit
        }

        $limit = Profile::where('id', $user->profile_id)->value('max_discount_percent');
        return new self($limit !== null ? (float) $limit : null);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->maxAllowed === null) {
            return; // no limit set
        }

        if ((float) $value > $this->maxAllowed) {
            $fail("O desconto máximo permitido para o seu perfil é de {$this->maxAllowed}%.");
        }
    }
}
