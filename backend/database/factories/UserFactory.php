<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'avatar' => null,
            'is_active' => true,
            'last_login_at' => null,
            'preferences' => [
                'theme' => fake()->randomElement(['light', 'dark']),
                'language' => fake()->randomElement(['pt-BR', 'en']),
                'notifications' => fake()->boolean(80)
            ],
            'tenant_id' => function () {
                return Tenant::factory()->create()->id;
            },
            'profile_id' => function () {
                return Profile::factory()->create()->id;
            },
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user has a tenant.
     */
    public function withTenant(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => Tenant::factory(),
        ]);
    }

    /**
     * Indicate that the user has logged in recently.
     */
    public function recentLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_login_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_id' => Profile::factory()->admin(),
        ]);
    }

    /**
     * Indicate that the user has specific preferences.
     */
    public function withPreferences(array $preferences): static
    {
        return $this->state(fn (array $attributes) => [
            'preferences' => array_merge($attributes['preferences'] ?? [], $preferences),
        ]);
    }
}