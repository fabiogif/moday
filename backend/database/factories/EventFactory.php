<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $types = ['promocao', 'aviso', 'outro'];
        $startDate = fake()->dateTimeBetween('now', '+30 days');

        return [
            'uuid' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'title' => fake()->sentence(3),
            'type' => fake()->randomElement($types),
            'start_date' => $startDate,
            'duration_minutes' => fake()->randomElement([30, 60, 90, 120, 180, 240]),
            'location' => fake()->boolean(70) ? fake()->address() : null,
            'description' => fake()->paragraph(3),
            'is_active' => true,
            'notifications_sent' => false,
        ];
    }

    /**
     * Event type: promocao
     */
    public function promocao(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'promocao',
            'title' => 'Promoção ' . fake()->words(2, true),
        ]);
    }

    /**
     * Event type: aviso
     */
    public function aviso(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'aviso',
            'title' => 'Aviso: ' . fake()->words(3, true),
        ]);
    }

    /**
     * Inactive event
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Notifications already sent
     */
    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notifications_sent' => true,
        ]);
    }

    /**
     * Past event
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Upcoming event
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }
}

