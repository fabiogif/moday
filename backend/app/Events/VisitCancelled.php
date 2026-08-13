<?php

namespace App\Events;

use App\Models\Visit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Visit $visit)
    {
    }
}
