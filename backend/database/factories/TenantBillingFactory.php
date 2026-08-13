<?php

namespace Database\Factories;

use App\Models\TenantBilling;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TenantBilling>
 */
class TenantBillingFactory extends Factory
{
    protected $model = TenantBilling::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $billingDate = now()->subDays(rand(0, 60));
        
        return [
            'tenant_id' => Tenant::factory(),
            'invoice_number' => 'INV-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
            'billing_date' => $billingDate,
            'due_date' => $billingDate->copy()->addDays(7),
            'amount' => [79, 149, 299][rand(0, 2)],
            'status' => 'pending',
            'plan_name' => ['Básico', 'Pro', 'Enterprise'][rand(0, 2)],
            'description' => 'Assinatura mensal',
            'paid_at' => null,
            'payment_method' => null,
        ];
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => ['credit_card', 'pix', 'bank_slip'][rand(0, 2)],
        ]);
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => now()->subDays(rand(1, 30)),
        ]);
    }
}

