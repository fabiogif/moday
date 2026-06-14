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
            'name' => $this->faker->unique()->words(2, true), // Gera pelo menos 2 palavras juntas
            'description' => $this->faker->sentence(),
            'image' => 'teste_factory.png',
            'price' => $this->faker->randomFloat(2, 10, 100),
            'price_cost' => $this->faker->randomFloat(2, 5, 80),
            'qtd_stock' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
