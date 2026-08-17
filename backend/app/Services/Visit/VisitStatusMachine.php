<?php

namespace App\Services\Visit;

use App\Exceptions\Visit\VisitInvalidTransitionException;
use App\Models\Visit;

/**
 * Transições válidas de status de visita. Classe stateless — não persiste nada,
 * apenas responde "essa transição é permitida?". Quem persiste (e grava o
 * histórico/dispara eventos) é App\Services\Visit\VisitService.
 */
class VisitStatusMachine
{
    /** @var array<string, string[]> */
    private const TRANSITIONS = [
        'agendada' => ['em_andamento', 'cancelada', 'reagendada', 'cliente_ausente'],
        'em_andamento' => ['concluida', 'sem_sucesso', 'cancelada'],
        'concluida' => [],
        'cancelada' => [],
        'reagendada' => [],
        'cliente_ausente' => [],
        'sem_sucesso' => [],
    ];

    public function can(string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * @throws VisitInvalidTransitionException
     */
    public function assertCan(string $from, string $to): void
    {
        if (!$this->can($from, $to)) {
            throw VisitInvalidTransitionException::forTransition($from, $to);
        }
    }

    public function requiresReason(string $to): bool
    {
        return in_array($to, ['cancelada', 'cliente_ausente'], true);
    }

    public function isTerminal(string $status): bool
    {
        return in_array($status, Visit::TERMINAL_STATUSES, true);
    }
}
