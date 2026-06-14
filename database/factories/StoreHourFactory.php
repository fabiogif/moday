<?php

namespace Database\Factories;

use App\Models\StoreHour;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreHourFactory extends Factory
{
    protected $model = StoreHour::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(6, 18);
        $endHour = $startHour + $this->faker->numberBetween(2, 8);
        
        return [
            'tenant_id' => Tenant::factory(),
            'is_always_open' => false,
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'delivery_type' => $this->faker->randomElement(['delivery', 'pickup', 'both']),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', min($endHour, 23)),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the store is always open.
     */
    public function alwaysOpen(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_always_open' => true,
            'day_of_week' => null,
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    /**
     * Indicate that the hour is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific day of week.
     */
    public function forDay(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
        ]);
    }

    /**
     * Set delivery type.
     */
    public function deliveryType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_type' => $type,
        ]);
    }

    /**
     * Set specific time range.
     */
    public function timeRange(string $start, string $end): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}

