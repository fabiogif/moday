<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);

        return [
            'tenant_id'         => Tenant::factory()->accessible(),
            'supplier_id'       => null,
            'status'            => 'rascunho',
            'subtotal'          => $subtotal,
            'discount_amount'   => 0,
            'tax_amount'        => 0,
            'freight_amount'    => 0,
            'total'             => $subtotal,
            'payment_term_days' => $this->faker->randomElement([0, 30, 60]),
        ];
    }

    public function enviado(): static
    {
        return $this->state(['status' => 'enviado']);
    }

    public function confirmado(): static
    {
        return $this->state(['status' => 'confirmado']);
    }

    public function recebido(): static
    {
        return $this->state(['status' => 'recebido']);
    }
}
