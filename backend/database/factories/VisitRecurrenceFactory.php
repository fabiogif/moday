<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VisitRecurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitRecurrenceFactory extends Factory
{
    protected $model = VisitRecurrence::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory()->accessible(),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'frequency' => 'weekly',
            'interval_count' => 1,
            'days_of_week' => [1],
            'day_of_month' => null,
            'scheduled_start_time' => '09:00',
            'scheduled_end_time' => '10:00',
            'type' => $this->faker->randomElement(\App\Models\Visit::TYPES),
            'priority' => 'normal',
            'starts_on' => now()->startOfDay()->format('Y-m-d'),
            'ends_on' => null,
            'is_active' => true,
        ];
    }
}
