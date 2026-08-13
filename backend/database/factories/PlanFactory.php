<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Generator as Faker;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'url' => fake()->unique()->slug(2),
            'price' => fake()->randomFloat(2, 0, 9999),
            'description' => fake()->sentence(),
            'is_active' => true,
            'max_users' => fake()->numberBetween(1, 1000),
            'max_products' => fake()->numberBetween(1, 10000),
            'max_orders_per_month' => fake()->numberBetween(10, 10000),
            'has_marketing' => fake()->boolean(),
            'has_order_completion_email' => fake()->boolean(),
            'has_reports' => fake()->boolean(),
        ];
    }
}
