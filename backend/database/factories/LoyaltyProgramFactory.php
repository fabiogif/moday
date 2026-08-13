<?php

namespace Database\Factories;

use App\Models\{LoyaltyProgram, Tenant};
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyProgramFactory extends Factory
{
    protected $model = LoyaltyProgram::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Programa ' . $this->faker->word(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'points_per_currency' => $this->faker->randomFloat(2, 0.5, 2),
            'min_purchase_amount' => $this->faker->optional()->randomFloat(2, 10, 50),
            'max_points_per_purchase' => $this->faker->optional()->numberBetween(100, 500),
            'points_expiry_days' => $this->faker->optional()->numberBetween(90, 365),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}

