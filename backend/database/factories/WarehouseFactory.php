<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'name'        => $this->faker->words(2, true) . ' Armazém',
            'description' => $this->faker->sentence(),
            'type'        => $this->faker->randomElement(['ambient', 'refrigerated', 'frozen']),
            'address'     => $this->faker->streetAddress(),
            'city'        => $this->faker->city(),
            'state'       => $this->faker->stateAbbr(),
            'is_active'   => true,
            'is_default'  => false,
        ];
    }
}
