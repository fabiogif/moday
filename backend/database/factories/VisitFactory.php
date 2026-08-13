<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory()->accessible(),
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'scheduled_start_time' => '09:00',
            'scheduled_end_time' => '10:00',
            'type' => $this->faker->randomElement(Visit::TYPES),
            'priority' => 'normal',
            'status' => 'agendada',
        ];
    }
}
