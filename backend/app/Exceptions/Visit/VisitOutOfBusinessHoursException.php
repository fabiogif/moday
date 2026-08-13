<?php

namespace App\Exceptions\Visit;

use Exception;

/**
 * Exceção "suave": sinaliza que o horário agendado está fora do horário comercial
 * do cliente, mas NÃO deve bloquear o agendamento — apenas ser exibida como aviso
 * (ver App\Services\Visit\VisitService::store/update).
 */
class VisitOutOfBusinessHoursException extends Exception
{
    public function __construct(string $businessHoursStart, string $businessHoursEnd)
    {
        parent::__construct("O horário agendado está fora do horário comercial do cliente ({$businessHoursStart} - {$businessHoursEnd}).");
    }
}
