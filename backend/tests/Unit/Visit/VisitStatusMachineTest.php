<?php

namespace Tests\Unit\Visit;

use App\Exceptions\Visit\VisitInvalidTransitionException;
use App\Services\Visit\VisitStatusMachine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class VisitStatusMachineTest extends TestCase
{
    private VisitStatusMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->machine = new VisitStatusMachine();
    }

    #[Test]
    #[TestWith(['agendada', 'em_andamento'])]
    #[TestWith(['agendada', 'cancelada'])]
    #[TestWith(['agendada', 'reagendada'])]
    #[TestWith(['agendada', 'cliente_ausente'])]
    #[TestWith(['em_andamento', 'concluida'])]
    #[TestWith(['em_andamento', 'sem_sucesso'])]
    #[TestWith(['em_andamento', 'cancelada'])]
    public function it_allows_valid_transitions(string $from, string $to): void
    {
        $this->assertTrue($this->machine->can($from, $to));
        $this->machine->assertCan($from, $to);
        $this->addToAssertionCount(1);
    }

    #[Test]
    #[TestWith(['concluida', 'agendada'])]
    #[TestWith(['cancelada', 'agendada'])]
    #[TestWith(['reagendada', 'em_andamento'])]
    #[TestWith(['cliente_ausente', 'concluida'])]
    #[TestWith(['sem_sucesso', 'agendada'])]
    #[TestWith(['agendada', 'concluida'])]
    #[TestWith(['agendada', 'sem_sucesso'])]
    #[TestWith(['em_andamento', 'agendada'])]
    #[TestWith(['agendada', 'agendada'])]
    public function it_rejects_invalid_transitions(string $from, string $to): void
    {
        $this->assertFalse($this->machine->can($from, $to));
        $this->expectException(VisitInvalidTransitionException::class);
        $this->machine->assertCan($from, $to);
    }

    #[Test]
    public function it_requires_reason_only_for_cancellation_and_client_absent(): void
    {
        $this->assertTrue($this->machine->requiresReason('cancelada'));
        $this->assertTrue($this->machine->requiresReason('cliente_ausente'));
        $this->assertFalse($this->machine->requiresReason('concluida'));
        $this->assertFalse($this->machine->requiresReason('em_andamento'));
    }

    #[Test]
    public function it_identifies_terminal_statuses(): void
    {
        $this->assertTrue($this->machine->isTerminal('concluida'));
        $this->assertTrue($this->machine->isTerminal('cancelada'));
        $this->assertTrue($this->machine->isTerminal('sem_sucesso'));
        $this->assertFalse($this->machine->isTerminal('agendada'));
        $this->assertFalse($this->machine->isTerminal('em_andamento'));
    }
}
