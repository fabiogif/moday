<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('###'),
            'name' => $this->faker->company(),
            'full_name' => $this->faker->company() . ' S.A.',
            'ispb' => $this->faker->numerify('########'),
            'supports_pix' => true,
            'is_active' => true,
        ];
    }

    /**
     * Banco inativo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Banco sem suporte a PIX
     */
    public function withoutPix(): static
    {
        return $this->state(fn (array $attributes) => [
            'supports_pix' => false,
        ]);
    }
}

