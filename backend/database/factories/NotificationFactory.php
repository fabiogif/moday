<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement([
                'user_created',
                'order_created',
                'order_status_changed',
                'product_stock_low',
                'client_created',
            ]),
            'channel' => $this->faker->randomElement(['email', 'push']),
            'title' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'data' => [
                'additional_info' => $this->faker->sentence(),
            ],
            'notifiable_type' => null,
            'notifiable_id' => null,
            'read_at' => null,
            'sent_at' => now(),
            'is_success' => true,
            'error_message' => null,
        ];
    }

    /**
     * Estado: não lida
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Estado: lida
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now()->subHours(rand(1, 24)),
        ]);
    }

    /**
     * Estado: falhada
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_success' => false,
            'sent_at' => null,
            'error_message' => 'Erro ao enviar notificação',
        ]);
    }
}

