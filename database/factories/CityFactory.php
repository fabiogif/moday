<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'state_id' => State::factory(),
            'ibge_code' => (string) $this->faker->unique()->numberBetween(1100000, 9999999),
            'name' => $this->faker->city(),
            'is_capital' => false,
        ];
    }

    /**
     * Indicate that the city is a capital.
     */
    public function capital(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_capital' => true,
        ]);
    }

    /**
     * Create a city for a specific state.
     */
    public function forState(int|State $state): static
    {
        $stateId = $state instanceof State ? $state->id : $state;

        return $this->state(fn (array $attributes) => [
            'state_id' => $stateId,
        ]);
    }

    /**
     * Create cities with specific names
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}

