<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\FinancialCategory;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $issueDate = $this->faker->dateTimeBetween('-3 months', 'now');
        $dueDate = (clone $issueDate)->modify('+' . $this->faker->numberBetween(7, 60) . ' days');
        
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => Tenant::factory(),
            'financial_category_id' => FinancialCategory::factory()->despesa(),
            'supplier_id' => Supplier::factory(),
            'payment_method_id' => null,
            'description' => $this->faker->randomElement([
                'Aluguel',
                'Energia Elétrica',
                'Água',
                'Internet',
                'Telefone',
                'Material de Limpeza',
                'Manutenção',
            ]) . ' - ' . $issueDate->format('m/Y'),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'payment_date' => null,
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'status' => $this->faker->randomElement(['pendente', 'pago', 'cancelado']),
            'recurrence' => $this->faker->randomElement(['unico', 'mensal', 'trimestral', 'anual']),
            'notes' => $this->faker->optional()->sentence(),
            'attachment_path' => null,
        ];
    }

    public function pendente(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendente',
            'payment_date' => null,
        ]);
    }

    public function pago(): self
    {
        return $this->state(function (array $attributes) {
            $dueDate = $attributes['due_date'];
            return [
                'status' => 'pago',
                'payment_date' => $this->faker->dateTimeBetween($dueDate, '+5 days'),
            ];
        });
    }

    public function vencida(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendente',
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}

