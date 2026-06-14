<?php

namespace Database\Factories;

use App\Models\DetailsPlan;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DetailsPlan>
 */
class DetailsPlanFactory extends Factory
{
    protected $model = DetailsPlan::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
        ];
    }
}

