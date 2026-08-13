<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethods = [
            'Dinheiro' => 'Pagamento em dinheiro',
            'Cartão de Crédito' => 'Pagamento com cartão de crédito',
            'Cartão de Débito' => 'Pagamento com cartão de débito',
            'PIX' => 'Pagamento via PIX',
            'Boleto Bancário' => 'Pagamento com boleto bancário',
            'Transferência Bancária' => 'Pagamento via transferência bancária',
            'Vale Alimentação' => 'Pagamento com vale alimentação',
            'Vale Refeição' => 'Pagamento com vale refeição',
            'Cheque' => 'Pagamento com cheque',
            'Crediário' => 'Pagamento parcelado no crediário',
        ];

        $name = $this->faker->randomElement(array_keys($paymentMethods));

        return [
            'uuid' => Str::uuid(),
            'name' => $name,
            'description' => $paymentMethods[$name],
            'tenant_id' => $this->faker->numberBetween(1, 10),
            'is_active' => $this->faker->boolean(80), // 80% de chance de estar ativo
        ];
    }

    /**
     * Indicate that the payment method is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the payment method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific tenant.
     */
    public function forTenant(int $tenantId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a cash payment method.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Dinheiro',
            'description' => 'Pagamento em dinheiro',
        ]);
    }

    /**
     * Create a PIX payment method.
     */
    public function pix(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'PIX',
            'description' => 'Pagamento via PIX',
        ]);
    }

    /**
     * Create a credit card payment method.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cartão de Crédito',
            'description' => 'Pagamento com cartão de crédito',
        ]);
    }
}