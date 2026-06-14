<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Plan;
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
        $slug = Str::slug($name);
        $subdomain = Str::slug($name . '-' . Str::random(4));
        
        return [
            'plan_id' => Plan::factory(), // Create plan automatically
            'uuid' => Str::uuid(),
            'cnpj' => fake()->numerify('##.###.###/0001-##'),
            'name' => $name,
            'email' => fake()->companyEmail(),
            'slug' => $slug,
            'subdomain' => $subdomain,
            'url' => "{$subdomain}.example.test",
            'logo' => null,
            'active' => 'Y',
            'account_status' => 'active',
            'activated_at' => now(),
            'trial_started_at' => now()->subDay(),
            'trial_expires_at' => now()->addDays(30),
        ];
    }

    /**
     * Tenant em período de trial (sem acesso garantido se trial_expires_at não definido).
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'trial',
            'trial_started_at' => now(),
            'trial_expires_at' => now()->addDays(7),
            'activated_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => 'N',
        ]);
    }

    /**
     * Tenant com acesso liberado (passa trial.check nos testes e middleware).
     */
    public function accessible(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => 'active',
            'activated_at' => now(),
            'trial_started_at' => now()->subDay(),
            'trial_expires_at' => now()->addDays(30),
        ]);
    }
}