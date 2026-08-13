<?php

namespace Database\Factories;

use App\Models\OrderStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderStatusFactory extends Factory
{
    protected $model = OrderStatus::class;

    public function definition(): array
    {
        $statuses = [
            'Pedido Recebido' => '#3B82F6',
            'Em Preparação / Cozinha' => '#FCD34D',
            'Pronto / Finalizado pela Cozinha' => '#34D399',
            'Em Entrega / Saiu para Entrega' => '#60A5FA',
            'Entregue' => '#10B981',
            'Cancelado' => '#EF4444',
        ];

        $status = fake()->randomElement(array_keys($statuses));
        $color = $statuses[$status];
        // Sufixo único evita colisão UNIQUE(tenant_id, slug) ao criar vários status
        $slug = Str::slug($status) . '-' . fake()->unique()->numerify('###');

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => $status,
            'slug' => $slug,
            'description' => fake()->sentence(),
            'color' => $color,
            'icon' => fake()->randomElement(['package', 'clock', 'truck', 'check-circle', 'x-circle']),
            'order_position' => fake()->numberBetween(1, 10),
            'is_initial' => false,
            'is_final' => in_array($status, ['Entregue', 'Cancelado']),
            'is_active' => true,
        ];
    }

    public function initial(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_initial' => true,
            'is_final' => false,
        ]);
    }

    public function final(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_initial' => false,
            'is_final' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

