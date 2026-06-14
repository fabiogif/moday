<?php

namespace Database\Factories;

use App\Models\SaleOrder;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleOrderFactory extends Factory
{
    protected $model = SaleOrder::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);

        return [
            'tenant_id'         => Tenant::factory()->accessible(),
            'status'            => 'orcamento',
            'subtotal'          => $subtotal,
            'discount_amount'   => 0,
            'tax_amount'        => 0,
            'freight_amount'    => 0,
            'total'             => $subtotal,
            'payment_term_days' => $this->faker->randomElement([0, 30, 60, 90]),
            'ordered_at'        => now(),
        ];
    }

    public function aprovado(): static
    {
        return $this->state(['status' => 'aprovado']);
    }

    public function entregue(): static
    {
        return $this->state(['status' => 'entregue']);
    }

    public function cancelado(): static
    {
        return $this->state(['status' => 'cancelado']);
    }
}
