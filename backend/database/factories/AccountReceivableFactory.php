<?php

namespace Database\Factories;

use App\Models\AccountReceivable;
use App\Models\Client;
use App\Models\FinancialCategory;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountReceivableFactory extends Factory
{
    protected $model = AccountReceivable::class;

    public function definition(): array
    {
        $issueDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $dueDate = $this->faker->dateTimeBetween($issueDate, '+60 days');
        
        $status = $this->faker->randomElement(['pendente', 'recebido', 'parcial', 'vencido', 'cancelado']);
        $amount = $this->faker->randomFloat(2, 100, 10000);
        
        $receiptDate = null;
        $amountReceived = null;
        
        if ($status === 'recebido') {
            $receiptDate = $this->faker->dateTimeBetween('now', '+30 days');
            $amountReceived = $amount;
        } elseif ($status === 'parcial') {
            $receiptDate = $this->faker->dateTimeBetween('now', '+30 days');
            $amountReceived = $amount * $this->faker->randomFloat(2, 0.3, 0.9);
        }

        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => Tenant::factory(),
            'description' => $this->faker->randomElement([
                'Venda de Produtos - ' . $this->faker->monthName,
                'Prestação de Serviços',
                'Comissão sobre Vendas',
                'Recebimento de Cliente',
                'Aluguel de Espaço',
                'Licenciamento de Software',
                'Consultoria',
                'Treinamento',
            ]),
            'financial_category_id' => null,
            'client_id' => null,
            'order_id' => null, // Por padrão sem vínculo, usar state para vincular
            'payment_method_id' => null,
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'receipt_date' => $receiptDate?->format('Y-m-d'),
            'amount' => $amount,
            'amount_received' => $amountReceived,
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
            'receipt_date' => null,
            'amount_received' => null,
            'due_date' => now()->addDays($this->faker->numberBetween(1, 30))->format('Y-m-d'),
        ]);
    }

    public function recebido(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'recebido',
                'receipt_date' => now()->format('Y-m-d'),
                'amount_received' => $attributes['amount'] ?? 1000.00,
            ];
        });
    }

    public function vencido(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'vencido',
            'due_date' => now()->subDays($this->faker->numberBetween(1, 30))->format('Y-m-d'),
            'receipt_date' => null,
            'amount_received' => null,
        ]);
    }

    public function parcial(): self
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? 1000.00;
            
            return [
                'status' => 'parcial',
                'receipt_date' => now()->format('Y-m-d'),
                'amount_received' => $amount * 0.6,
            ];
        });
    }

    public function withOrder(Order $order): self
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'client_id' => $order->client_id ?? Client::factory(),
            'description' => 'Pedido #' . $order->id . ($attributes['installment_number'] ?? ''),
        ]);
    }

    public function withInstallments(int $number, int $total): self
    {
        return $this->state(fn (array $attributes) => [
            'installment_number' => $number,
            'total_installments' => $total,
            'description' => ($attributes['description'] ?? 'Recebimento') . " - Parcela {$number}/{$total}",
            'amount' => ($attributes['amount'] ?? 1000.00) / $total,
        ]);
    }
}

