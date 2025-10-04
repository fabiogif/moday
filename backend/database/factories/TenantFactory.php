<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'uuid' => Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'document' => fake()->numerify('##.###.###/0001-##'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zipcode' => fake()->postcode(),
            'country' => 'BR',
            'is_active' => true,
            'settings' => [
                'timezone' => 'America/Sao_Paulo',
                'currency' => 'BRL',
                'language' => 'pt-BR'
            ],
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the tenant has custom settings.
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($attributes['settings'] ?? [], $settings),
        ]);
    }
}