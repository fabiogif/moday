<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
           'tenant_id' => Tenant::factory(),
            'title' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'image' => 'teste_factory.png',
            'price' => $this->faker->randomFloat(2, 10, 100),
        ];
    }
}
