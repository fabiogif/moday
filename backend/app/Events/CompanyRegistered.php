<?php

namespace App\Events;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando uma nova empresa (tenant) se cadastra na plataforma
 */
class CompanyRegistered
{
    use Dispatchable, SerializesModels;

    public Tenant $tenant;
    public User $user;
    public Plan $plan;

    /**
     * Create a new event instance.
     */
    public function __construct(Tenant $tenant, User $user, Plan $plan)
    {
        $this->tenant = $tenant;
        $this->user = $user;
        $this->plan = $plan;
    }
}






