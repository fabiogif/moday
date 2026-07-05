<?php

namespace Database\Factories;

use App\Models\{LoyaltyTransaction, LoyaltyProgram, Client, Order};
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyTransactionFactory extends Factory
{
    protected $model = LoyaltyTransaction::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['earn', 'redeem']);
        $points = $type === 'earn' 
            ? $this->faker->numberBetween(10, 500)
            : -$this->faker->numberBetween(10, 500);
        
        return [
            'loyalty_program_id' => LoyaltyProgram::factory(),
            'client_id' => Client::factory(),
            'type' => $type,
            'points' => $points,
            'balance_after' => $this->faker->numberBetween(0, 1000),
            'order_id' => $type === 'earn' ? Order::factory() : null,
            'purchase_amount' => $type === 'earn' ? $this->faker->randomFloat(2, 50, 500) : null,
            'multiplier' => 1.0,
            'description' => $this->faker->sentence(),
            'expires_at' => $type === 'earn' ? $this->faker->optional()->dateTimeBetween('now', '+1 year')?->format('Y-m-d') : null,
        ];
    }

    public function earn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earn',
            'points' => $this->faker->numberBetween(10, 500),
        ]);
    }

    public function redeem(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'redeem',
            'points' => -$this->faker->numberBetween(10, 500),
        ]);
    }
}

