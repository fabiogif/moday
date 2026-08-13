<?php

namespace Database\Factories;

use App\Models\{LoyaltyRedemption, LoyaltyReward, Client, LoyaltyTransaction};
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyRedemptionFactory extends Factory
{
    protected $model = LoyaltyRedemption::class;

    public function definition(): array
    {
        return [
            'loyalty_reward_id' => LoyaltyReward::factory(),
            'client_id' => Client::factory(),
            'loyalty_transaction_id' => LoyaltyTransaction::factory(),
            'points_used' => $this->faker->numberBetween(100, 1000),
            'status' => 'pending',
            'redeemed_at' => now()->format('Y-m-d'),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+90 days')->format('Y-m-d'),
        ];
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'used',
            'used_at' => now()->format('Y-m-d'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1)->format('Y-m-d'),
        ]);
    }
}

