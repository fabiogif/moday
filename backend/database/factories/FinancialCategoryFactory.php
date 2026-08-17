<?php

namespace Database\Factories;

use App\Models\FinancialCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialCategoryFactory extends Factory
{
    protected $model = FinancialCategory::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['receita', 'despesa']);
        
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->unique()->words(2, true), // Nome único
            'type' => $type,
            'description' => $this->faker->optional()->sentence(),
            'color' => $this->faker->randomElement([
                '#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6',
                '#ec4899', '#f97316', '#06b6d4', '#6366f1', '#6b7280'
            ]),
            // Default ativo: filtros da API usam scope active(); boolean(95) deixava
            // testes de listagem por tipo flaky no CI.
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function receita(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'receita',
            'color' => '#10b981', // Verde
        ]);
    }

    public function despesa(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'despesa',
            'color' => '#ef4444', // Vermelho
        ]);
    }
}

