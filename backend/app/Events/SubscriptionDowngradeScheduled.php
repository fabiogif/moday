<?php

namespace App\Events;

use App\Models\Plan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionDowngradeScheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly Plan   $fromPlan,
        public readonly Plan   $toPlan,
        public readonly Carbon $effectiveDate,
    ) {}
}
