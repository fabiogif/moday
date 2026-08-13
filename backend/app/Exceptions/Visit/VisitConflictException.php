<?php

namespace App\Exceptions\Visit;

use App\Models\Visit;
use Exception;

class VisitConflictException extends Exception
{
    public function __construct(private readonly Visit $conflictingVisit)
    {
        parent::__construct('O vendedor já possui uma visita agendada nesse horário.');
    }

    public function getConflictingVisit(): Visit
    {
        return $this->conflictingVisit;
    }
}
