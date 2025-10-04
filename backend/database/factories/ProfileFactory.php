<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['user', 'manager', 'admin']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the profile is for an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'description' => 'Administrador do sistema',
        ]);
    }

    /**
     * Indicate that the profile is for a manager user.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'manager',
            'description' => 'Gerente do sistema',
        ]);
    }

    /**
     * Indicate that the profile is for a regular user.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'user',
            'description' => 'Usuário padrão',
        ]);
    }

    /**
     * Indicate that the profile is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
