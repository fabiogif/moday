<?php

namespace Database\Factories;

use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceTypeFactory extends Factory
{
    protected $model = ServiceType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        
        return [
            'uuid' => Str::uuid(),
            'identify' => Str::slug($name),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(90),
            'requires_address' => $this->faker->boolean(30),
            'requires_table' => $this->faker->boolean(30),
            'available_in_menu' => $this->faker->boolean(50),
            'order_position' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function availableInMenu(): self
    {
        return $this->state(fn (array $attributes) => [
            'available_in_menu' => true,
            'is_active' => true,
        ]);
    }

    public function requiresAddress(): self
    {
        return $this->state(fn (array $attributes) => [
            'requires_address' => true,
        ]);
    }

    public function requiresTable(): self
    {
        return $this->state(fn (array $attributes) => [
            'requires_table' => true,
        ]);
    }
}

