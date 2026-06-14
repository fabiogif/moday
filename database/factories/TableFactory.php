<?php

namespace Database\Factories;

use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Table>
 */
class TableFactory extends Factory
{
    protected $model = Table::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'identify' => strtoupper(Str::random(6)),
            'name' => 'Mesa ' . $this->faker->unique()->numerify('##'),
            'description' => $this->faker->sentence(),
            'capacity' => $this->faker->numberBetween(2, 8),
            'uuid' => Str::uuid(),
            'is_active' => true,
        ];
    }
}


