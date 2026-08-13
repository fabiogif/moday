<?php

namespace Database\Factories;

use App\Models\PriceTable;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceTableFactory extends Factory
{
    protected $model = PriceTable::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'name'        => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type'        => 'padrao',
            'is_active'   => true,
        ];
    }
}
