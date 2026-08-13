<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement([
                'user_created',
                'order_created',
                'order_status_changed',
                'product_stock_low',
                'client_created',
            ]),
            'email_enabled' => true,
            'push_enabled' => true,
            'sms_enabled' => false,
            'frequency' => 'instant',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '06:00',
        ];
    }

    /**
     * Estado: apenas email habilitado
     */
    public function emailOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_enabled' => true,
            'push_enabled' => false,
            'sms_enabled' => false,
        ]);
    }

    /**
     * Estado: apenas push habilitado
     */
    public function pushOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_enabled' => false,
            'push_enabled' => true,
            'sms_enabled' => false,
        ]);
    }

    /**
     * Estado: tudo desabilitado
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_enabled' => false,
            'push_enabled' => false,
            'sms_enabled' => false,
        ]);
    }
}

