<?php

namespace App\Events;

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\PlanMigration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando uma empresa confirma a adesão a um plano
 * ou migra para um novo plano
 */
class PlanConfirmed
{
    use Dispatchable, SerializesModels;

    public Tenant $tenant;
    public Plan $plan;
    public ?Plan $oldPlan;
    public ?PlanMigration $migration;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Tenant $tenant,
        Plan $plan,
        ?Plan $oldPlan = null,
        ?PlanMigration $migration = null
    ) {
        $this->tenant = $tenant;
        $this->plan = $plan;
        $this->oldPlan = $oldPlan;
        $this->migration = $migration;
    }
}






