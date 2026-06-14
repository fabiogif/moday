<?php

namespace Database\Factories;

use App\Models\{LoyaltyReward, LoyaltyProgram, Product};
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyRewardFactory extends Factory
{
    protected $model = LoyaltyReward::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['discount_percentage', 'discount_fixed', 'free_product', 'free_shipping']);
        
        return [
            'loyalty_program_id' => LoyaltyProgram::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $type,
            'points_required' => $this->faker->numberBetween(100, 1000),
            'discount_value' => in_array($type, ['discount_percentage', 'discount_fixed']) 
                ? $this->faker->randomFloat(2, 5, 50) 
                : null,
            'product_id' => $type === 'free_product' ? Product::factory() : null,
            'stock_quantity' => $this->faker->optional()->numberBetween(10, 100),
            'max_redemptions_per_user' => $this->faker->optional()->numberBetween(1, 5),
            'validity_days' => $this->faker->optional()->numberBetween(30, 90),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}

