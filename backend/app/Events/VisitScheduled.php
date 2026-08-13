<?php

namespace App\Events;

use App\Models\Visit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitScheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Visit $visit)
    {
    }
}
