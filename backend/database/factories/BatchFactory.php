<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        $qty = $this->faker->randomFloat(3, 10, 500);

        return [
            'tenant_id'          => Tenant::factory(),
            'product_id'         => Product::factory(),
            'warehouse_id'       => Warehouse::factory(),
            'batch_number'       => strtoupper($this->faker->bothify('LOTE-####')),
            'manufacture_date'   => now()->subMonths(2),
            'expiry_date'        => now()->addMonths(12),
            'quantity_received'  => $qty,
            'quantity_available' => $qty,
            'quantity_reserved'  => 0,
            'quantity_sold'      => 0,
            'unit_cost'          => $this->faker->randomFloat(4, 1, 100),
            'status'             => 'available',
        ];
    }

    public function expiringSoon(int $days = 15): static
    {
        return $this->state(['expiry_date' => now()->addDays($days)]);
    }

    public function expired(): static
    {
        return $this->state(['expiry_date' => now()->subDays(5)]);
    }
}
