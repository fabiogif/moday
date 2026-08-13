<?php

namespace App\Events;

use App\Models\Visit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitRescheduled
{
    use Dispatchable, SerializesModels;

    /**
     * @param  Visit  $visit  a nova visita já reagendada (agendada)
     * @param  Visit  $originalVisit  a visita original, agora com status "reagendada"
     */
    public function __construct(public readonly Visit $visit, public readonly Visit $originalVisit)
    {
    }
}
