<?php

namespace App\Exceptions\Visit;

use Exception;

class VisitInvalidTransitionException extends Exception
{
    public static function forTransition(string $from, string $to): self
    {
        return new self("Não é possível alterar a visita de \"{$from}\" para \"{$to}\".");
    }
}
