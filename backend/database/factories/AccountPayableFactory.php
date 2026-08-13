<?php

namespace Database\Factories;

use App\Models\AccountPayable;
use App\Models\FinancialCategory;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountPayableFactory extends Factory
{
    protected $model = AccountPayable::class;

    public function definition(): array
    {
        $issueDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $dueDate = $this->faker->dateTimeBetween($issueDate, '+60 days');
        
        $status = $this->faker->randomElement(['pendente', 'pago', 'parcial', 'vencido', 'cancelado']);
        $amount = $this->faker->randomFloat(2, 50, 5000);
        
        $paymentDate = null;
        $amountPaid = null;
        
        if ($status === 'pago') {
            $paymentDate = $this->faker->dateTimeBetween('now', '+30 days');
            $amountPaid = $amount;
        } elseif ($status === 'parcial') {
            $paymentDate = $this->faker->dateTimeBetween('now', '+30 days');
            $amountPaid = $amount * $this->faker->randomFloat(2, 0.3, 0.9);
        }

        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => Tenant::factory(),
            'description' => $this->faker->randomElement([
                'Aluguel - ' . $this->faker->monthName,
                'Energia Elétrica',
                'Água e Esgoto',
                'Internet e Telefonia',
                'Salário - ' . $this->faker->name,
                'Fornecedor - ' . $this->faker->company,
                'Manutenção de Equipamentos',
                'Material de Limpeza',
                'Compra de Mercadorias',
            ]),
            'financial_category_id' => null,
            'supplier_id' => null,
            'payment_method_id' => null,
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'payment_date' => $paymentDate?->format('Y-m-d'),
            'amount' => $amount,
            'amount_paid' => $amountPaid,
            'discount' => $this->faker->optional(0.2)->randomFloat(2, 0, $amount * 0.1),
            'interest' => $this->faker->optional(0.1)->randomFloat(2, 0, $amount * 0.05),
            'fine' => $this->faker->optional(0.1)->randomFloat(2, 0, $amount * 0.02),
            'status' => $status,
            'document_number' => $this->faker->optional()->numerify('NF-####'),
            'notes' => $this->faker->optional()->sentence(),
            'installment_number' => null,
            'total_installments' => null,
        ];
    }

    public function pendente(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendente',
            'payment_date' => null,
            'amount_paid' => null,
            'due_date' => now()->addDays($this->faker->numberBetween(1, 30))->format('Y-m-d'),
        ]);
    }

    public function pago(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pago',
                'payment_date' => now()->format('Y-m-d'),
                'amount_paid' => $attributes['amount'] ?? 1000.00,
            ];
        });
    }

    public function vencido(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'vencido',
            'due_date' => now()->subDays($this->faker->numberBetween(1, 30))->format('Y-m-d'),
            'payment_date' => null,
            'amount_paid' => null,
        ]);
    }

    public function parcial(): self
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? 1000.00;
            
            return [
                'status' => 'parcial',
                'payment_date' => now()->format('Y-m-d'),
                'amount_paid' => $amount * 0.5,
            ];
        });
    }

    public function withInstallments(int $number, int $total): self
    {
        return $this->state(fn (array $attributes) => [
            'installment_number' => $number,
            'total_installments' => $total,
            'description' => ($attributes['description'] ?? 'Pagamento') . " - Parcela {$number}/{$total}",
        ]);
    }
}

