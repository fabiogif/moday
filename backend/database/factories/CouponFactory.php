<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'uuid' => $this->faker->uuid,
            'code' => strtoupper($this->faker->unique()->word . $this->faker->numberBetween(10, 99)),
            'name' => 'Cupom ' . $this->faker->word,
            'description' => $this->faker->sentence,
            'discount_type' => $this->faker->randomElement(['percentage', 'fixed']),
            'discount_value' => $this->faker->randomFloat(2, 5, 50),
            'start_at' => now(),
            'end_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}